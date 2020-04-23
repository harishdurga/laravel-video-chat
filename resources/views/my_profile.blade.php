@extends('layouts.app')
@section('content')
    <div class="container">
        <h3>My Profile</h3>
        <form action="{{route('my-profile')}}" method="post">
            @csrf
            <div class="form-group">
                <label for="name">Name <span class="text-danger">*</span></label>
                <input required type="text" class="form-control" id="name" name="name" value="{{$user->name}}">
            </div>
            <div class="form-group">
                <label for="preferred_language">Preferred Language</label>
                <select required name="preferred_language" id="preferred_language" class="form-control">
                    @foreach ($languages as $item)
                        <option @if($item->lang_code == $user->preferred_language) selected @endif value="{{$item->lang_code}}">{{$item->trans_name}} <small>({{$item->eng_name}})</small> </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-success">Save</button>
            </div>
        </form>
    </div>
@endsection