<template>
  <div class="container-fluid">
    <div class="video-chat-outer-container">
      <div class="row">
        <div class="col-md-2 px-0">
          <friends-list></friends-list>
        </div>
        <div class="col-md-7 bg-light" id="video-component">
          <twillio-video></twillio-video>
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
      <div class="col-md-12"></div>
    </div>
  </div>
</template>

<script>
import TextChatComponent from "./chat/TextChatComponent";
import FriendsList from "./chat/FriendsList";
import SearchUser from "./chat/SearchUser";
import FriendRequests from "./chat/FriendRequests";
import TwillioVideoChat from "./chat/TwillioVideoChat";
import Peer from "simple-peer";
const APP_KEY = "ABCD123";
const APP_CLUSTER = "mt1";
export default {
  created() {
    this.setupPusher();
  },
  mounted() {
    this.getInitData();
    // this.joinOnlineChannel();
    // this.getUserMedia();
  },
  data() {
    return {
      otherUserId: null,
      pusher: null,
      user: window.user,
      channel: null,
      clientMessageChannel: null,
      otherUserClientMessageChannel: null,
      online_users: [],
      friends: [],
      typing_timeout: null,
      is_typing: false,
      selected_user: null,
      is_online: false,
      room: null,
    };
  },
  methods: {
    setupPusher() {
      this.channel = Echo.private(`App.User.${this.user.id}`);
      this.clientMessageChannel = Echo.private(
        `ClientMessages.${this.user.id}`
      );
      this.clientMessageChannel.listenForWhisper(`typing-signal`, (signal) => {
        if (this.selected_user) {
          if (this.selected_user.id != signal.userId) {
            //As the signal is not from the currently selected user
            return false;
          }
        } else {
          //As no user is selected no need to show the typing signal
          return false;
        }

        clearTimeout(this.typing_timeout);
        this.is_typing = true;
        var thiss = this;
        this.typing_timeout = setTimeout(function () {
          thiss.is_typing = false;
        }, 1000);
      });
    },
    joinOnlineChannel() {
      Echo.join("online")
        .here((users) => {
          this.online_users = users;
          this.checkForFriendsOnline();
        })
        .joining((user) => {
          this.online_users.push(user);
          this.checkForFriendsOnline();
        })
        .leaving((user) => {
          this.online_users = this.online_users.filter((u) => u.id !== user.id);
          this.checkForFriendsOnline();
        });
    },
    checkIfUserOnline(userId) {
      var thiss = this;
      this.is_online = false;
      this.online_users.forEach((element) => {
        if (element.id == userId) {
          thiss.is_online = true;
          return;
        }
      });
    },
    getInitData() {
      Vue.axios.get("/get-init-data").then((response) => {
        this.friends = response.data.friends;
        this.checkForFriendsOnline();
      });
    },
    checkForFriendsOnline() {
      var thiss = this;
      this.friends.forEach((friend) => {
        thiss.online_users.forEach((online_user) => {
          if (friend.id == online_user.id) {
            friend.is_online = true;
            return;
          }
          friend.is_online = false;
        });
      });
    },
  },
  components: {
    "text-chat": TextChatComponent,
    "friends-list": FriendsList,
    "search-user": SearchUser,
    "friend-requests": FriendRequests,
    "twillio-video": TwillioVideoChat,
  },
  watch: {
    selected_user() {
      this.otherUserClientMessageChannel = Echo.private(
        `ClientMessages.${this.selected_user.id}`
      );
      this.checkIfUserOnline(this.selected_user.id);
    },
  },
};
</script>
<style>
.my-video-container {
  position: absolute;
  top: 0;
  right: 0;
}
.video-container {
  position: relative;
  /* background-color: #9a9999; */
  border: solid 1px #cccccc;
}
video.my-video {
  height: 100%;
  width: 200px;
}
video.user-video {
  width: 100%;
  height: 500px;
}
#video-component {
  background-color: #fffde1;
  height: 500px;
  border: solid 1px #ffeb00;
}
</style>
