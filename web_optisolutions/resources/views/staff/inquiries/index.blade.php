@extends('staff.layouts.app')

@section('content')
@include('staff.inquiries.partials.inquiries-styles')

<div class="container">
    <div class="page-title-group" style="margin-bottom: 1rem;">
        <h3 style="margin: 0; font-size: 1.75rem; color: var(--primary-dark); display: flex; align-items: center;">
            <span class="inq-header-icon"><i class="bi bi-chat-square-text"></i></span> Chatbot Inquiries
        </h3>
        <p class="page-subtitle">Review and respond to patient conversations from the AI chatbot</p>
    </div>

    <div class="inquiries-split inquiries-split--list-only">
      
        @include('staff.inquiries.partials.conversations-list', ['inquiries' => $inquiries])


        <div class="conv-detail-panel">
            <div class="conv-detail-empty">
                Select a conversation to view messages.
            </div>
        </div>
    </div>
</div>
@endsection