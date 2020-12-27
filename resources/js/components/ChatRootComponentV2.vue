<template>
  <div>
    <h1>This is New Chat Root component</h1>
    <div>
      <button
        @click="startCall"
        class="btn btn-success"
        :disabled="outGoingCallStatus != 0"
      >
        <span v-if="outGoingCallStatus == 0">Call</span>
        <span v-if="outGoingCallStatus == 1">Calling</span>
      </button>
    </div>
  </div>
</template>
<script>
export default {
  name: "ChatRootComponentV2",
  data() {
    return {
      user: window.user,
      privateChannel: null,
      outGoingCallStatus: 0,
    };
  },
  mounted() {
    this.setupChannel();
  },
  methods: {
    setupChannel() {
      this.privateChannel = Echo.private(`App.User.${this.user.id}`);
      this.privateChannel.listen("IncomingCall", (e) => {
        console.log("Incoming call");
        this.handleIncomingCall(e.data);
      });
      this.privateChannel.listen("IncomingCallStatus", (e) => {
        console.log("IncomingCallStatus");
        this.handleIncomingCallStatus(e.data);
      });
      this.privateChannel.listen("NewMessage", (e) => {
        console.log("NewMessage");
        console.log(e);
      });
    },
    startCall() {
      this.outGoingCallStatus = 1;
      Vue.axios.post("/call-user", { recipient_id: 4 }).then((response) => {
        // console.log(response.data);
      });
    },
    handleIncomingCall(data) {
      console.log("handleIncomingCall");
      console.log(data);
      if (
        confirm(
          data.caller.name +
            " is calling you. Would you like to answer the call?"
        )
      ) {
        //
        Vue.axios
          .post("/call-status", {
            caller_id: data.caller.id,
            call_status: "accept",
          })
          .then((response) => {
            console.log(response.data);
          });
      } else {
        console.log("Incoming call rejected");
        Vue.axios
          .post("/call-status", {
            caller_id: data.caller.id,
            call_status: "reject",
          })
          .then((response) => {
            console.log(response.data);
          });
      }
    },
    handleIncomingCallStatus(data) {
      console.log(data);
      if (data.call_status == "reject") {
        this.outGoingCallStatus = 0;
        alert("User rejected to answer your call");
      } else {
        alert("User accepted to answer your call");
      }
    },
  },
};
</script>