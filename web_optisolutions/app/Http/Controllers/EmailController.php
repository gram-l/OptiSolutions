use Illuminate\Support\Facades\Mail;
use App\Mail\AppointmentConfirmation;

Mail::to($request->email)->send(
    new AppointmentConfirmation(
        $request->name,
        $request->date,
        $doctor->name
    )
);