{{ $alertTitle }}

{{ $alertMessage }}
@if ($inquiryId !== null)
Reference: INQUIRY-{{ $inquiryId }}
@endif

A chatbot inquiry needs attention. Sign in and open Chatbot Inquiries to view the conversation and reply.
Open PolyClinic: {{ $loginUrl }}
