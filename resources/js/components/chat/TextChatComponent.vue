<template>
    <div>
        <div class="text-chat-outer-container">
            <div class="chat-container">
                <ul class="list-unstyled">
                    <li class="" v-for="i in 100" :key="i">
                        <div class="chat-bubble shadow-sm p-1 mb-2 rounded">
                            <div class="d-flex">
                                <div class="sender pr-2">
                                    <b>Strong Name:</b>
                                </div>
                                <div class="message">
                                    This is some message isihosd
                                </div>
                            </div>
                            <div class="small org-message">
                                Lorem, ipsum dolor sit
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="text-input-container">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" placeholder="Type Your message" aria-label="Type Your message" aria-describedby="basic-addon2">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button"><i class="far fa-paper-plane"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    name:"TextChatComponent",
    data() {
        return {
            
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