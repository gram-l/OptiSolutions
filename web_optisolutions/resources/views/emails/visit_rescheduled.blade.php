<!DOCTYPE html>
<html lang="en"><body style="font-family:Arial,sans-serif;color:#16324a;line-height:1.6">
<h2>Your updated PolyClinic visit</h2>
<p>Hello {{ $patientName }},</p>
<p>Your scheduled visit has been updated. Please use these details:</p>
<p><strong>Doctor:</strong> {{ $doctorName }}<br><strong>Service:</strong> {{ $service }}<br>
<strong>Date and time:</strong> {{ $scheduleDetails }} (Philippine time)<br><strong>Visit reference:</strong> {{ $visitId }}</p>
<p>Please contact the clinic if you need help with your new schedule.</p>
</body></html>
