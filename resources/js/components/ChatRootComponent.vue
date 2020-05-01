<template>
    <div class="container-fluid">
        <div class="video-chat-outer-container">
            <div class="row">
                <div class="col-md-2 px-0">
                    <friends-list></friends-list>
                </div>
                <div class="col-md-7 bg-light" id="video-component">
                    <twillio-video></twillio-video>
                    <div v-if="false" class="video-container shadow-sm">
                        <div  class="user-video-container">
                            <video :srcObject.prop="userVideoSrc" ref="userVideo" autoplay="autoplay" class="user-video w-100"></video>
                        </div>
                        <div class="my-video-container">
                            <video :srcObject.prop="myVideoSrc" ref="myVideo" class="my-video" autoplay="autoplay"></video>
                        </div>
                        <div class="bottom-action-bar p-2 bg-white text-center">
                            <button class="btn btn-danger rounded" @click="stopMedia" title="Stop Media"><i class="fas fa-video-slash"></i></button>
                            <button :disabled="!is_online" @click="callTo" class="btn btn-success rounded"><i class="fas fa-phone-alt"></i></button>
                            <button class="btn btn-danger rounded"><i class="fa fa-phone-slash"></i></button>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 px-0">
                    <text-chat :selected_user="selected_user"></text-chat>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <h3 class="text-uppercase">Search A User</h3>
                <search-user></search-user>
            </div>
            <div class="col-md-6">
                <h3 class="text-uppercase">Friend Requests</h3>
                <friend-requests></friend-requests>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                
            </div>
        </div>
    </div>
</template>

<script>
    import MediaHandler from "../MediaHandler";
    import TextChatComponent from "./chat/TextChatComponent"
    import FriendsList from './chat/FriendsList'
    import SearchUser from "./chat/SearchUser";
    import FriendRequests from "./chat/FriendRequests";
    import TwillioVideoChat from "./chat/TwillioVideoChat";
    import Peer from 'simple-peer';
    const APP_KEY = 'ABCD123';
    const APP_CLUSTER = 'mt1';
    export default {
        created() {
            
        },
        mounted() {
            this.setupPusher();
            this.getInitData();
            this.joinOnlineChannel();
            // this.getUserMedia();
        },
        data() {
            return {
                hasMedia:false,
                otherUserId:null,
                mediaHandler:new MediaHandler,
                myVideoSrc:null,
                userVideoSrc:null,
                pusher:null,
                user:window.user,
                channel:null,
                peers:{},
                online_users:[],
                friends:[],
                typing_timeout:null,
                is_typing:false,
                selected_user:null,
                is_online:false,
                iceServers:[],
                room:null
            }
        },
        methods: {
            setupPusher(){
                
                this.channel = Echo.private('presence-video-channel');
                this.channel.listenForWhisper(`client-signal-${this.user.id}`,(signal)=>{
                    //confirm(`Do you want to accept call this call from ${signal.userName}?`)
                    if(true){
                        let peer = this.peers[signal.userId];
                        //If peer is empty, means we got incoming call
                        if(peer === undefined){
                            this.otherUserId = signal.userId;
                            peer = this.startPeer(signal.userId,false);
                        }
                        peer.signal(signal.data);
                    }else{
                        this.channel.whisper(`callreject-signal-${signal.userId}`,{
                            type:'signal',
                            data:{}
                        })
                    }
                    
                })
                this.channel.listenForWhisper(`typing-signal-${this.user.id}`,(signal)=>{
                    clearTimeout(this.typing_timeout);
                    this.is_typing = true;
                    var thiss = this;
                    this.typing_timeout = setTimeout(function () {
                        thiss.is_typing = false
                    }, 1000);
                })
                this.channel.listenForWhisper(`callreject-signal-${this.user.id}`,(signal)=>{
                    this.$toasted.show("User rejected you call!",{type:'error'});
                })
            },
            startPeer(userId,initiator=true){
                console.log("Starting peer");
                if(!this.myVideoSrc){
                    this.getUserMedia();
                }
                const peer = new Peer({
                    initiator,
                    stream: this.myVideoSrc,
                    trickle:true,
                    config: { iceServers: this.iceServers 
                    }
                });
                peer.on('signal',(data)=>{
                    console.log("Peer On Signal");
                    this.channel.whisper(`client-signal-${userId}`,{
                        type:'signal',
                        userId:this.user.id,
                        userName:this.user.name,
                        data:data
                    })
                })
                peer.on('stream',(stream)=>{
                    console.log("Peer On Stream");
                    try {
                        this.userVideoSrc = stream;
                    } catch (error) {
                        this.userVideoSrc = URL.createObjectURL(stream);
                    }
                })
                peer.on('close',()=>{
                    let peer = this.peers[userId];
                    if(peer !== undefined){
                        peer.destroy();
                    }
                    this.peers[userId] = undefined;
                })
                return peer;
            },
            callTo(){
                if(this.selected_user == null){
                    this.$toasted.show('Please select a user from the friends on the left side!',{type:'error'})
                    return false;
                }
                if(this.iceServers.length == 0){
                    this.$toasted.show('Sorry! No ice servers found!',{type:'error'})
                    return false;
                }
                
                var userId = this.selected_user.id;
                this.peers[userId] = this.startPeer(userId,true);
            },
            stopMedia(){
                this.myVideoSrc.getTracks().forEach(function(track) {
                    track.stop();
                });
            },
            joinOnlineChannel(){
                Echo.join('online')
                .here(users => {this.online_users = users;this.checkForFriendsOnline()})
                .joining(user => {this.online_users.push(user);this.checkForFriendsOnline()})
                .leaving(user => {this.online_users = this.online_users.filter(u => (u.id !== user.id));this.checkForFriendsOnline()})
            },
            getUserMedia(){
                this.mediaHandler.getPermissions().then((stream)=>{
                    this.hasMedia = true;
                    try {
                        this.myVideoSrc = stream;
                    } catch (error) {
                        this.myVideoSrc = URL.createObjectURL(stream);
                    }
                })
            },
            checkIfUserOnline(userId){
                var thiss = this;
                this.is_online = false;
                this.online_users.forEach(element => {
                    if(element.id == userId){
                        thiss.is_online = true;
                        return;
                    }
                });
            },
            getInitData(){
                Vue.axios.get('/get-init-data').then((response) => {
                    this.friends = response.data.friends;
                    this.iceServers = response.data.iceServers;
                    this.checkForFriendsOnline();
                })
            },
            checkForFriendsOnline(){
                var thiss = this;
                this.friends.forEach((friend)=>{
                    thiss.online_users.forEach((online_user)=>{
                        if(friend.id == online_user.id){
                            friend.is_online = true;
                            return;
                        }
                        friend.is_online = false;
                    })
                })
            }
        },
        components:{
            'text-chat':TextChatComponent,
            'friends-list':FriendsList,
            'search-user':SearchUser,
            'friend-requests':FriendRequests,
            'twillio-video':TwillioVideoChat
        },
        watch: {
            'selected_user'(){
                this.checkIfUserOnline(this.selected_user.id);
            },
            
        },
    }
</script>
<style>
.my-video-container{
    position: absolute;
    top: 0;
    right: 0;
}
.video-container{
    position: relative;
    background-color: #9a9999;
    border: solid 1px #cccccc;
}
video.my-video{
    height: 100%;
    width: 200px;
}
video.user-video{
    width: 100%;
    height: 500px;
}
#video-component{
    background-color: #e6e6e6;
    height: 500px;
    border: solid 2px #a2a2a2;
}
</style>
