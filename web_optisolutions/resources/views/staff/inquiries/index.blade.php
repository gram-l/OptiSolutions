@extends('staff.layouts.app')

@section('content')
@include('staff.inquiries.partials.inquiries-styles')

<div class="container">
    <div class="page-title-group" style="margin-bottom: 1rem;">
        <h3 style="margin: 0;">
            <i class="bi bi-chat-square-text"></i> Chatbot Inquiries
        </h3>
        <p class="page-subtitle">View and respond to chatbot inquiries</p>
    </div>

    <div class="inquiries-split">
      
        @include('staff.inquiries.partials.conversations-list', ['inquiries' => $inquiries])


        <div class="conv-detail-panel">
            <div class="conv-detail-empty">
                Select a conversation to view messages.
            </div>
        </div>
    </div>
</div>
@endsection