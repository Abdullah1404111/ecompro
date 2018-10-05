@extends('user_layout')

@section('content')
<div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
            <div class="row">
                <div class="col-md-6">
                    <label>Name</label>
                </div>
                <div class="col-md-6">
                    <p>{{ Session::get('user_name') }}</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label>Email</label>
                </div>
                <div class="col-md-6">
                    <p>{{ Session::get('user_email') }}</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label>Phone</label>
                </div>
                <div class="col-md-6">
                    <p>{{ Session::get('user_mobile') }}</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <label>Profession</label>
                </div>
                <div class="col-md-6">
                    <p>Web Developer and Designer</p>
                </div>
            </div>
</div>

@endsection
