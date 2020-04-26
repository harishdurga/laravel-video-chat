<template>
    <div>
        <form v-on:submit.prevent="searchUser">
            <div class="input-group mb-3">
                <input v-model="keyword" type="text" class="form-control" placeholder="Recipient's username" aria-label="Recipient's username" aria-describedby="basic-addon2">
                <div class="input-group-append">
                    <button type="submit" class="btn btn-outline-secondary">Search</button>
                </div>
            </div>
        </form>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Friend Status</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(val,index) in users" :key="index">
                    <td>{{val.name}}</td>
                    <td>
                        <button @click="addAsFriend(index)" class="btn btn-primary" v-if="!val.is_friend" title="Add As Friend"><i class="fas fa-user-plus"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
export default {
    name:"SearchUser",
    data(){
        return{
            keyword:'',
            is_waiting:false,
            users:[]
        }
    },
    methods:{
        searchUser(){
            if(this.keyword.length == 0){
                this.$toasted.show('Please enter a keyword to search!',{type:'error'})
                return false;
            }
            Vue.axios.get('/search-users?keyword='+this.keyword).then((response) => {
                this.users = response.data.users;
            })
        },
        addAsFriend(index){
            Vue.axios.post('/add-friend',{'user_id':this.users[index].id}).then((response) => {
                if(response.data.status){
                    this.$toasted.show(response.data.message,{type:'success'})
                }else{
                    this.$toasted.show(response.data.message,{type:'error'})
                }
            })
        }
    }
}
</script>