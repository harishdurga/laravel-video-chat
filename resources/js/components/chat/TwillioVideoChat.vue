<template>
    <div>
        <h1>This is twillio</h1>
        <div id="video-chat-window" class="grid grid-flow-row grid-cols-2 grid-rows-2 gap-4"></div>
    </div>
</template>
<script>
export default {
    name:"TwillioVideoChat",
    data(){
        return{
            accessToken:''
        }
    },
    methods:{
        getAccessToken : function () {
            // Request a new token
            Vue.axios.get('/twillio_access_token')
                .then( (response)=> {
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

            connect( this.accessToken, { name:'OneToOne' }).then(room => {
                
                console.log(`Successfully joined a Room: ${room}`);
                room.participants.forEach(participant => {
                    console.log(`Participant "${participant.identity}" is connected to the Room`);
                });
                
                const videoChatWindow = document.getElementById('video-chat-window');

                createLocalVideoTrack().then(track => {
                    videoChatWindow.appendChild(track.attach());
                });

                room.on('participantConnected', participant => {
                    console.log(`Participant "${participant.identity}" connected`);
                    participant.tracks.forEach(publication => {
                        if (publication.isSubscribed) {
                            const track = publication.track;
                            videoChatWindow.appendChild(track.attach());
                        }
                    });
                    participant.on('trackSubscribed', track => {
                        videoChatWindow.appendChild(track.attach());
                    });
                });
            }, error => {
                console.error(`Unable to connect to Room: ${error.message}`);
            });
        }
    },
    mounted : function () {
        console.log('Video chat room loading...')
        this.getAccessToken()
    }
}
</script>
<style>
div#video-chat-window video:first-child {
    width: 125px;
    position: absolute;
    right: 0;
}
#video-chat-window video:nth-child(2) {
    height: 380px;
    margin: auto;
    display: block;
}
</style>