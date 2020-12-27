<template>
  <div>
    <div class="text-chat-outer-container">
      <div id="chat-container" class="chat-container p-2">
        <ul class="list-unstyled">
          <li
            class=""
            v-for="(message, index) in previous_messages"
            :key="index"
          >
            <!-- <div class="chat-bubble shadow-sm p-1 mb-2 rounded">
              <div class="d-flex">
                <div class="sender pr-2">
                  <b>{{ message.sender }}:</b>
                </div>
                <div class="message">
                  {{
                    $parent.user.id == message.sender_id
                      ? message.translated_message
                      : message.message
                  }}
                </div>
              </div>
              <div class="small org-message">
                {{
                  $parent.user.id == message.sender_id
                    ? message.message
                    : message.translated_message
                }}
              </div>
              <div class="message-time text-right">
                <span>{{ message.time }}</span>
              </div>
            </div> -->
            <text-chat-bubble :message="message" />
          </li>
        </ul>
      </div>
      <div class="typing-animation">
        <img
          v-if="$parent.is_typing"
          class="typing-gif"
          src="/images/typing.gif"
          alt=""
        />
      </div>
      <div class="text-input-container">
        <form v-on:submit.prevent="sendMessage">
          <div class="input-group mb-3">
            <input
              @keyup="sendTypingSignal"
              :disabled="waiting || selected_user == null"
              v-model="message"
              type="text"
              class="form-control"
              placeholder="Type Your message"
              aria-label="Type Your message"
              aria-describedby="basic-addon2"
            />
            <div class="input-group-append">
              <button
                @click="sendMessage"
                :disabled="message.length == 0 || waiting"
                class="btn bg-theme-yellow"
                type="button"
              >
                <i class="far fa-paper-plane"></i>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
<script>
import TextChatBubble from "./TextChatBubble";
export default {
  name: "TextChatComponent",
  props: ["selected_user"],
  mounted() {
    this.$parent.channel.listen("NewMessage", (e) => {
      if (this.selected_user != null) {
        if (this.selected_user.id == e.data.sender_id) {
          this.previous_messages.push(e.data);
        } else {
          this.setBlinkingIcon(e.data.sender_id);
        }
      } else {
        this.setBlinkingIcon(e.data.sender_id);
      }
    });
  },
  data() {
    return {
      message: "",
      previous_messages: [],
      waiting: false,
    };
  },
  methods: {
    sendMessage() {
      if (this.message.length == 0) {
        return false;
      }
      this.previous_messages.push({
        message: this.message,
        recipient_id: this.selected_user.id,
        sender: this.$parent.user.name,
        sender_id: this.$parent.user.id,
        time: "",
        translated_message: "",
        is_new: true,
      });
      this.message = "";
      this.scrollToBottom();
      return true;
      this.waiting = true;
      Vue.axios
        .post("/send-new-message", {
          message: this.message,
          recipient_id: this.selected_user.id,
        })
        .then((response) => {
          this.previous_messages.push(response.data);
          this.message = "";
          this.waiting = false;
          this.scrollToBottom();
        });
    },
    sendTypingSignal() {
      if (this.message.length > 0) {
        this.$parent.otherUserClientMessageChannel.whisper(`typing-signal`, {
          type: "signal",
          userId: this.$parent.user.id,
          data: {},
        });
      }
    },
    getPastMessages() {
      Vue.axios
        .get("/previous-messages/" + this.selected_user.id)
        .then((response) => {
          this.previous_messages = response.data.previous_messages;
          this.scrollToBottom();
        });
    },
    setBlinkingIcon(userId) {
      var thiss = this;
      this.$parent.friends.forEach((element) => {
        if (element.id == userId) {
          element.new_message = true;
          return;
        }
      });
    },
    scrollToBottom() {
      var container = this.$el.querySelector("#chat-container");
      var st = container.scrollHeight;
      $("#chat-container").animate({ scrollTop: st + 1000 }, "slow");
    },
  },
  watch: {
    selected_user() {
      this.getPastMessages();
    },
  },
  components: {
    "text-chat-bubble": TextChatBubble,
  },
};
</script>
<style>
.chat-container {
  height: 415px;
  overflow-y: auto;
}
.chat-bubble {
  background: #fff;
}
.chat-bubble .sender {
  color: crimson;
}
.chat-bubble .sender.self {
  color: #0094ff;
}
.chat-bubble .org-message {
  color: #626263;
}
.text-chat-outer-container {
  background: #e6e6e6 !important;
}
.typing-animation {
  height: 50px;
}
.typing-gif {
  height: 40px;
}
.message-time {
  font-size: x-small;
}
</style>