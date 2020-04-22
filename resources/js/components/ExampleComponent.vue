<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Example Component</div>

                    <div class="card-body">
                        <button @click="callTo(2)">Call Other User 2</button>
                        <button @click="callTo(1)">Call Other User 1</button>
                        <div class="row">
                            <div class="col-6">
                                <video width="320" height="240" :srcObject.prop="myVideoSrc" ref="myVideo" class="my-video" autoplay="autoplay"></video>
                            </div>
                            <div class="col-6">
                                <video width="320" height="240" :srcObject.prop="userVideoSrc" ref="userVideo" autoplay="autoplay" class="user-video"></video>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import MediaHandler from "../MediaHandler";
    import Pusher from 'pusher-js';
    import Peer from 'simple-peer';
    const APP_KEY = 'ABCD123';
    const APP_CLUSTER = 'mt1';
    export default {
        created() {
            
        },
        mounted() {
            this.setupPusher();
            this.mediaHandler.getPermissions().then((stream)=>{
                this.hasMedia = true;
                try {
                    this.myVideoSrc = stream;
                } catch (error) {
                    this.myVideoSrc = URL.createObjectURL(stream);
                }
            })

            Echo.private(`NewMessage.User.${this.user.id}`)
            .listen('NewMessage', (e) => {
                console.log("Got New Message");
                console.log(e.message);
            });
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
                peers:{}
            }
        },
        methods: {
            setupPusher(){
                
                this.channel = Echo.private('presence-video-channel');
                this.channel.listenForWhisper(`client-signal-${this.user.id}`,(signal)=>{
                    console.log("client-signal came");
                    let peer = this.peers[signal.userId];
                    //If peer is empty, means we got incoming call
                    if(peer === undefined){
                        this.otherUserId = signal.userId;
                        peer = this.startPeer(signal.userId,false);
                    }
                    peer.signal(signal.data);
                })
            },
            startPeer(userId,initiator=true){
                console.log("Starting peer");
                const peer = new Peer({
                    initiator,
                    stream: this.myVideoSrc,
                    trickle:false
                });
                peer.on('signal',(data)=>{
                    console.log("Peer On Signal");
                    this.channel.whisper(`client-signal-${userId}`,{
                        type:'signal',
                        userId:this.user.id,
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
            callTo(userId){
                console.log(`Calling ${userId}`)
                this.peers[userId] = this.startPeer(userId,true);
            }
        },
    }
</script>
