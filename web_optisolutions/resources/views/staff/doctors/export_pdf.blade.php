<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1a1a1a; }
        h2 { font-size: 14px; margin: 18px 0 2px; }
        .specialty { color: #555; margin: 0 0 6px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ddd; padding: 5px 8px; text-align: left; }
        th { background: #f2f2f2; }
        .no-patients { color: #888; font-style: italic; padding: 6px 0; }
        .print-btn { display: inline-block; margin-bottom: 16px; padding: 10px 18px; background: #1e3a5f; color: #fff; border: none; border-radius: 8px; font-size: 13px; cursor: pointer; }
        @media print {
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">Print / Save as PDF</button>
    <h1 style="font-size: 18px;">Doctors and their patients</h1>
    <p style="color:#555;">Generated {{ now()->format('F j, Y g:i A') }}</p>

    @foreach($doctors as $doctor)
        <h2>{{ $doctor->doctor_name }}</h2>
        <p class="specialty">{{ $doctor->specialty }}</p>

        @if($doctor->patients->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th>Patient name</th>
                        <th>Email</th>
                        <th>Contact number</th>
                        <th>Birthdate</th>
                        <th>Last visit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($doctor->patients as $patient)
                        <tr>
                            <td>{{ $patient->full_name }}</td>
                            <td>{{ $patient->patient_email }}</td>
                            <td>{{ $patient->patient_contact }}</td>
                            <td>{{ $patient->patient_birthdate }}</td>
                            <td>{{ $patient->latestVisit->visit_date ?? 'No visits yet' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="no-patients">No patients recorded.</p>
        @endif
    @endforeach
</body>
</html>