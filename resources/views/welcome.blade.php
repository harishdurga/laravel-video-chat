@extends('layouts.app')
@section('content')
    <div class="d-flex align-items-center justify-content-center content">
        <div class="">
            <div class="">
                <h1 class="display-2 text-center"><i class="fas fa-video"></i> {{config('app.name')}}</h1>
            </div>
            <div>
                <h4 class="text-center">Laravel | Vue | Twilio Video| Websockets | Google Translate</h4>
            </div>
            <div class="text-center px-4 mt-4">
                <p class="tagline">Language Not A Barrier For Communication</p>
            </div>
            
        </div>
    </div>
@endsection
@section('style')
    <style>
        .content{
            height: 80vh;
        }
        .tagline{
            display: inline;
            background: #212529;
            padding: 8px 16px;
            color: white;
            font-weight: bolder;
            /* margin-top: 25px; */
            border-radius: 50px;
        }
    </style>
@endsection