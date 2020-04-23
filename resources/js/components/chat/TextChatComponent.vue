<template>
    <div>
        <div class="text-chat-outer-container">
            <div class="chat-container">
                <ul class="list-unstyled">
                    <li class="" v-for="(message,index) in previous_messages" :key="index">
                        <div class="chat-bubble shadow-sm p-1 mb-2 rounded">
                            <div class="d-flex">
                                <div class="sender pr-2">
                                    <b>{{message.sender}}:</b>
                                </div>
                                <div class="message">
                                    {{$parent.user.id == message.sender_id?message.translated_message:message.message}}
                                </div>
                            </div>
                            <div class="small org-message">
                                {{$parent.user.id == message.sender_id?message.message:message.translated_message}}
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="text-input-container">
                <form v-on:submit.prevent="sendMessage">
                    <div class="input-group mb-3">
                        <input :disabled="waiting" v-model="message" type="text" class="form-control" placeholder="Type Your message" aria-label="Type Your message" aria-describedby="basic-addon2">
                        <div class="input-group-append">
                            <button @click="sendMessage" :disabled="(message.length == 0) || waiting" class="btn btn-outline-secondary" type="button"><i class="far fa-paper-plane"></i></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    name:"TextChatComponent",
    mounted(){
        Echo.private(`NewMessage.User.${this.$parent.user.id}`)
            .listen('NewMessage', (e) => {
                this.previous_messages.push(e.data);
            });
    },
    data() {
        return {
            message:'',
            previous_messages:[],
            waiting:false
        }
    },
    methods: {
        sendMessage (){
            if(this.message.length == 0){
                return false;
            }
            this.waiting = true;
            Vue.axios.post('/send-new-message',{'message':this.message,'recipient_id':2}).then((response) => {
                this.previous_messages.push(response.data);
                this.message = "";
                this.waiting = false;
            })
        }
    },
}
</script>
<style>
    .chat-container{
        height: 500px;
        overflow-y: auto;
    }
    .chat-bubble{
        background: #eaeaea;
    }
    .chat-bubble .sender{
        color: crimson;
    }
    .chat-bubble .org-message{
        color: #626263;
    }
</style>