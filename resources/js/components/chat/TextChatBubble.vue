<template>
  <div>
    <div class="chat-bubble shadow-sm p-1 mb-2 rounded">
      <div class="d-flex">
        <div class="sender pr-2">
          <b>{{ message.sender }}:</b>
        </div>
        <div class="message">
          {{
            $parent.$parent.user.id == message.sender_id
              ? message.translated_message
              : message.message
          }}
        </div>
      </div>
      <div class="small org-message">
        {{
          $parent.$parent.user.id == message.sender_id
            ? message.message
            : message.translated_message
        }}
      </div>
      <div class="message-time text-right">
        <span>{{ message.time }}</span>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  name: "TextChatBubble",
  data() {
    return {};
  },
  props: ["message"],
  mounted() {
    if (this.message.is_new) {
      Vue.axios
        .post("/send-new-message", {
          message: this.message.message,
          recipient_id: this.message.recipient_id,
        })
        .then((response) => {
          this.message.translated_message = response.data.translated_message;
          this.message.time = response.data.time;
        });
    }
  },
};
</script>
