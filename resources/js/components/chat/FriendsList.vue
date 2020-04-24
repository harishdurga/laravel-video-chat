<template>
    <div>
        <div class="friendlist-container">
            <h3 class="text-center bg-white shadow-sm py-2">Friends</h3>
            <div class="friendlist">
                <ul class="list-unstyled">
                    <li @click="friendSelected(index)" v-for="(val,index) in $parent.friends" :key="index" :class="{'bg-primary text-white':val.is_selected,'bg-light':!val.is_selected}" class="friendlist-item my-2 p-2">
                        {{val.name}}
                    </li>
                </ul>
            </div>
        </div>
        
    </div>
</template>
<script>
export default {
    name:"FriendsList",
    data() {
        return {
            
        }
    },
    methods: {
        friendSelected(index){
            this.$parent.friends.forEach(element => {
                element.is_selected = false;
            });
            this.$parent.friends[index].is_selected = true;
            this.$parent.selected_user = this.$parent.friends[index];
        }
    }
    
}
</script>
<style>
    .friendlist-container{
        background: #e6e6e6;
        height: 500px;
        overflow-y: auto;
    }
    .friendlist-item{
        cursor: pointer;
    }
</style>