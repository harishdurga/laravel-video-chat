<template>
    <div>
        <div>
            <button title="Join Room" @click="joinRoom" class="btn btn-success rounded"><i class="fas fa-phone-alt"></i></button>
            <button class="btn btn-danger rounded" @click="disConnectFromRoom" title="Disconnect From Room"><i class="fas fa-video-slash"></i></button>
        </div>
        <div>
            <span v-if="is_loading"><i class="fas fa-cog fa-spin"></i></span> <span>{{loading_message}}</span>
        </div>
        <div id="video-chat-window" class="grid grid-flow-row grid-cols-2 grid-rows-2 gap-4">
            <div id="remote-media"></div>
            <div id="local-media"></div>
        </div>
    </div>
</template>
<script>
export default {
    name:"TwillioVideoChat",
    data(){
        return{
            accessToken:'',
            room:null,
            is_loading:false,
            loading_message:''
        }
    },
    methods:{
        getAccessToken : function () {
            // Request a new token
            this.is_loading = true;
            this.loading_message = "Fetching credentials...."
            Vue.axios.get('/twillio_access_token')
                .then( (response)=> {
                    this.loading_message = "Credentials fetched. Now joining a room";
                    this.accessToken = response.data
                })
                .catch(function (error) {
                    console.log(error);
                })
                .then(()=> {
                    this.connectToRoom()
                });
        },
        connectToRoom : function () {

            const { connect, createLocalVideoTrack } = require('twilio-video');
            this.createLocalVideoTrack = createLocalVideoTrack;
            connect( this.accessToken, { name:'OneToOne' }).then(room => {
                this.is_loading = false;
                this.loading_message = "Successfully joined the room!";
                this.room = room;
                console.log(`Successfully joined a Room: ${room}`);
                room.participants.forEach(participant => {
                    console.log(`Participant "${participant.identity}" is connected to the Room`);
                    var previewContainer = document.getElementById('remote-media');
                    this.participantConnected(participant);
                });
                
                const videoChatWindow = document.getElementById('video-chat-window');

                createLocalVideoTrack().then(track => {
                    const localMediaContainer = document.getElementById('local-media');
                    localMediaContainer.appendChild(track.attach());
                });

                room.on('participantConnected', participant => {
                    console.log(`Participant "${participant.identity}" connected`);
                    document.getElementById('remote-media').innerHTML = "";
                    participant.tracks.forEach(publication => {
                        if (publication.isSubscribed) {
                            const track = publication.track;
                            document.getElementById('remote-media').appendChild(track.attach());
                        }
                    });
                    participant.on('trackSubscribed', track => {
                        document.getElementById('remote-media').appendChild(track.attach());
                    });
                });
                room.on('disconnected', room => {
                    // Detach the local media elements
                    room.localParticipant.tracks.forEach(publication => {
                        const attachedElements = publication.track.detach();
                        attachedElements.forEach(element => element.remove());
                    });
                });
            }, error => {
                console.error(`Unable to connect to Room: ${error.message}`);
            });
        },
        disConnectFromRoom(){
            if(confirm("Do you really want to disconnect from room?")){
                this.room.disconnect();
                alert("Disconnected From Room");
            }
            
        },
        joinRoom(){
            if(confirm("Do you really want to join this room?")){
                if(this.room == null){
                    this.getAccessToken()
                }else{
                    alert("You already joined the room!")
                }
            }
        },
        participantConnected(participant){
            participant.tracks.forEach(publication => {
                this.trackPublished(publication, participant);
            });
        },
        trackPublished(publication,participant){
            if (publication.track) {
                this.attachTrack(publication.track, participant);
            }

            // Once the TrackPublication is subscribed to, attach the Track to the DOM.
            publication.on('subscribed', track => {
                this.attachTrack(track, participant);
            });

            // Once the TrackPublication is unsubscribed from, detach the Track from the DOM.
            publication.on('unsubscribed', track => {
                this.detachTrack(track, participant);
            });
        },
        attachTrack(track,participant){
            document.getElementById('remote-media').innerHTML = "";
            document.getElementById('remote-media').appendChild(track.attach());
            // track.attach();
        },
        detachTrack(track){
            track.detach();
        }
        
    },
    mounted : function () {
        
    }
}
</script>
<style>
#local-media video{
    width: 125px;
    position: absolute;
    right: 0;
}
#remote-media video{
    height: 380px;
    margin: auto;
    display: block;
}
#local-media{
    position: absolute;
    top: 0;
    right: 0;
}
</style>