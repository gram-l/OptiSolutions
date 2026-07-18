@extends('staff.layouts.app')

@section('content')
@include('staff.inquiries.partials.inquiries-styles')

<div class="container">
    <div class="action-bar">
        <h3>Chatbot Inquiries</h3>
    </div>

    <div class="inquiries-split">
        {{-- LEFT: Recent Conversations --}}
        @include('staff.inquiries.partials.conversations-list', ['inquiries' => $inquiries])

        {{-- RIGHT: wala pang napipiling conversation --}}
        <div class="conv-detail-panel">
            <div class="conv-detail-empty">
                Select a conversation to view messages.
            </div>
        </div>
    </div>
</div>
@endsection