<template>
    <div>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Accept/Reject</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(val,index) in requests" :key="index">
                    <td>{{val.name}}</td>
                    <td>
                        <button title="Accept Request" @click="acceptRejectRequest(true,index)" class="btn btn-sm btn-success"><i class="fas fa-check-circle"></i></button>
                        <button title="Reject Request" @click="acceptRejectRequest(false,index)" class="btn btn-sm btn-danger"><i class="fas fa-times-circle"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
export default {
    name:"FriendRequests",
    data(){
        return{
            requests:[]
        }
    },
    mounted(){
        this.getInitData()
    },
    methods:{
        getInitData(){
            Vue.axios.get('/friend-requests').then((response)=>{
                this.requests = response.data.requests;
            })
        },
        acceptRejectRequest(action,index){
            if(confirm(`Are you sure to ${action?'accept':'reject'} this request from ${this.requests[index].name}?`)){
                Vue.axios.post('/accept-reject-request',{'action':action,'id':this.requests[index].id}).then((response)=>{
                    if(response.data.status){
                        this.$toasted.show(response.data.message,{type:'success'})
                        this.requests.splice(index,1);
                    }else{
                        this.$toasted.show(response.data.message,{type:'error'})
                    }
                })
            }
            
        }
    }
}
</script>