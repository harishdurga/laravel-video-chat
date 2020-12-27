<template>
  <div>
    <div class="p-2">
      <button
        title="Start Video Call"
        @click="startVideoCall"
        class="btn bg-theme-yellow rounded shadow-sm"
        :disabled="!$parent.selected_user"
      >
        <i class="fas fa-phone-alt"></i>
      </button>
      <button
        class="btn btn-dark rounded shadow-sm"
        @click="disConnectFromRoom"
        title="End Video Call"
        :disabled="!$parent.selected_user"
      >
        <i class="fas fa-video-slash"></i>
      </button>
    </div>
    <div>
      <span v-if="is_loading"><i class="fas fa-cog fa-spin"></i></span>
      <span>{{ loading_message }}</span>
    </div>
    <div
      id="video-chat-window"
      class="grid grid-flow-row grid-cols-2 grid-rows-2 gap-4"
    >
      <div id="remote-media"></div>
      <div id="local-media"></div>
    </div>
  </div>
</template>
<script>
export default {
  name: "TwillioVideoChat",
  data() {
    return {
      accessToken: "",
      room: null,
      is_loading: false,
      loading_message: "",
      room_name: "",
      channel: null,
      outGoingCallStatus: 0,
      localVideoTrack: null,
    };
  },
  methods: {
    getAccessToken: function (caller_id = "", recipient_id = "") {
      console.log("getAccessToken " + caller_id);
      // Request a new token
      this.is_loading = true;
      this.loading_message = "Fetching credentials....";
      Vue.axios
        .post("/video-call/token", {
          caller_id: caller_id,
          recipient_id: recipient_id,
        })
        .then((response) => {
          this.loading_message = "Credentials fetched. Now joining a room";
          this.accessToken = response.data.token;
          this.room_name = response.data.room_sid;
        })
        .catch(function (error) {
          console.log(error);
        })
        .then(() => {
          // this.callRecipient();
          this.connectToRoom();
        });
    },
    connectToRoom: function () {
      const { connect, createLocalVideoTrack } = require("twilio-video");
      this.createLocalVideoTrack = createLocalVideoTrack;
      connect(this.accessToken, {
        name: this.room_name,
        audio: true,
        maxAudioBitrate: 16000,
        video: { height: 380, frameRate: 24, width: 600 },
      }).then(
        (room) => {
          this.is_loading = false;
          this.loading_message = "Successfully joined the room!";
          this.room = room;
          console.log(`Successfully joined a Room: ${room}`);
          room.participants.forEach((participant) => {
            console.log(
              `Participant "${participant.identity}" is connected to the Room`
            );
            var previewContainer = document.getElementById("remote-media");
            this.participantConnected(participant);
          });

          const videoChatWindow = document.getElementById("video-chat-window");
          createLocalVideoTrack().then((track) => {
            this.localVideoTrack = track;
            const localMediaContainer = document.getElementById("local-media");
            localMediaContainer.appendChild(track.attach());
          });

          room.on("participantConnected", (participant) => {
            console.log(`Participant "${participant.identity}" connected`);

            document.getElementById("remote-media").innerHTML = "";
            participant.tracks.forEach((publication) => {
              if (publication.isSubscribed) {
                const track = publication.track;
                document
                  .getElementById("remote-media")
                  .appendChild(track.attach());
              }
            });
            participant.on("trackSubscribed", (track) => {
              document
                .getElementById("remote-media")
                .appendChild(track.attach());
            });
          });
          room.on("disconnected", (room) => {
            // Detach the local media elements
            room.localParticipant.tracks.forEach((publication) => {
              const attachedElements = publication.track.detach();
              attachedElements.forEach((element) => element.remove());
            });
            this.localVideoTrack.stop();
          });
        },
        (error) => {
          console.error(`Unable to connect to Room: ${error.message}`);
        }
      );
    },
    disConnectFromRoom() {
      if (confirm("Do you really want to disconnect from room?")) {
        this.room.disconnect();
        alert("Disconnected From Room");
        Vue.axios
          .post("/video-call/complete", { room: this.room_name })
          .then((response) => {
            console.log(response.data);
          });
      }
    },
    startVideoCall() {
      if (this.$parent.selected_user) {
        this.$dialog
          .confirm(
            `Would you like to video call ${this.$parent.selected_user.name}?`
          )
          .then((dialog) => {
            this.is_loading = true;
            this.loading_message = `Calling ${this.$parent.selected_user.name} ...`;
            this.outGoingCallStatus = 1;
            Vue.axios
              .post("/video-call/call-user", {
                recipient_id: this.$parent.selected_user.id,
              })
              .then((response) => {
                // console.log(response.data);
              });
          })
          .catch(() => {
            console.log("Clicked on cancel");
          });
      } else {
        alert(
          "Please a select a friend from the friends list on the left side to make a video call!"
        );
      }
    },
    participantConnected(participant) {
      participant.tracks.forEach((publication) => {
        this.trackPublished(publication, participant);
      });
    },
    trackPublished(publication, participant) {
      if (publication.track) {
        this.attachTrack(publication.track, participant);
      }

      // Once the TrackPublication is subscribed to, attach the Track to the DOM.
      publication.on("subscribed", (track) => {
        this.attachTrack(track, participant);
      });

      // Once the TrackPublication is unsubscribed from, detach the Track from the DOM.
      publication.on("unsubscribed", (track) => {
        this.detachTrack(track, participant);
      });
    },
    attachTrack(track, participant) {
      document.getElementById("remote-media").innerHTML = "";
      document.getElementById("remote-media").appendChild(track.attach());
      // track.attach();
    },
    detachTrack(track) {
      track.detach();
    },
    postCallStatus(status, caller_id) {
      Vue.axios
        .post("/video-call/call-status", {
          caller_id: caller_id,
          call_status: status,
        })
        .then((response) => {
          console.log(response.data);
        });
    },
    handleIncomingCall(data) {
      console.log("handleIncomingCall");
      console.log(data);
      this.$dialog
        .confirm(
          data.caller.name +
            " is calling you. Would you like to answer the call?"
        )
        .then((dialog) => {
          console.log("Accepted Call From " + data.caller.id);
          this.postCallStatus("accept", data.caller.id);
          this.getAccessToken(data.caller.id, this.$parent.user.id);
        })
        .catch(() => {
          this.postCallStatus("reject", data.caller.id);
        });
    },
    //When the other user accepts or rejects the call this method will be called
    handleIncomingCallStatus(data) {
      console.log(data);
      if (data.call_status == "reject") {
        this.outGoingCallStatus = 0;
        alert("User declined to answer your call");
      } else {
        this.getAccessToken(
          this.$parent.user.id,
          this.$parent.selected_user.id
        );
      }
    },
  },
  mounted: function () {
    this.$parent.channel.listen("IncomingCall", (e) => {
      console.log("Incoming call");
      this.handleIncomingCall(e.data);
    });
    this.$parent.channel.listen("IncomingCallStatus", (e) => {
      console.log("IncomingCallStatus");
      this.handleIncomingCallStatus(e.data);
    });
  },
};
</script>
<style>
#local-media video {
  width: 125px;
  position: absolute;
  right: 0;
}
#remote-media video {
  height: 380px;
  margin: auto;
  display: block;
}
#local-media {
  position: absolute;
  top: 0;
  right: 0;
}
</style>