@php
    $layout = 'plugins/member::themes.dashboard.layouts.master';
@endphp

@extends('core/table::table')

@section('content')
    <h4>Your Referral Link:</h4> {!! Form::text('referral_link', auth('member')->user()->getReferralLink(),['class' => 'form-control mb-3', 'readonly' => true]) !!}
    @parent
@stop
