@isset($manualAudience)
<div class="settings-user-manual">
    <p><strong>PolyClinic-Lipa Website User Manual</strong></p>
    <p>System Website: OptiSolutions: A Customer Inquiry and Analytics System with Sentiment Analysis</p>
    @if($manualAudience === 'admin')
        <p>Website Users: Patients/Website Visitors · Staff · Administrators</p>
        <p><strong>Who uses the website?</strong></p>
        <p>Patients or Website Visitors – They can look at clinic details, ask questions, schedule a visit, write a review or send a complaint. They do not need an account.</p>
        <p>Staff – They can handle appointments, answer questions, from patients, change doctor schedules and see records. They have to log in.</p>
        <p>Administrators – Can do all the things that Staff can do. They can also take care of accounts, website details, chatbot answers, patient feedback and settings. They have to log in.</p>
    @endif
    <details class="manual-section">
        <summary>Patient and Website Visitor Manual</summary>
        <h5 class="manual-line">1.1 Open the Website and Read the Privacy Notice</h5>
        <p class="manual-line">Access the PolyClinic website by opening your browser on your mobile device or PC.</p>
        <p class="manual-line">The first time you visit a website, a window called “We Have a New General Privacy Notice” will appear. It explains what information the website asks for when you book a visit like: your name, contact number, date of birth, email address, chosen service, and doctor. The website does not take payments.</p>
        <p class="manual-line">Click the link to read the full notice. When you are ready, click “I Agree” to continue. The notice will not appear again on that browser.</p>
        <p class="manual-line">You can read the Privacy Notice again anytime using the link at the bottom of the website.</p>
        <h5 class="manual-line">1.2 Find Information on the Home Page</h5>
        <p class="manual-line">The website is one long page. Use the menu at the top to go to the section you need.</p>
        <p class="manual-line">Home – Welcome message, shortcuts for patient feedback and finding a doctor, recent patient feedback, and clinic information.</p>
        <p class="manual-line">About Us – Information about the clinic, including its Mission, Vision, and Core Values.</p>
        <p class="manual-line">Services – A list of clinic services. Select a service or click “Learn More” to read about it.</p>
        <p class="manual-line">Doctors – Doctor profiles and photos. Use the tabs to show all doctors or doctors for a selected service.</p>
        <p class="manual-line">Reviews – Patient comments, the average rating, and the “Submit Feedback” button.</p>
        <p class="manual-line">Contact – Clinic address, phone number, opening hours, and directions.</p>
        <p class="manual-line">On a phone, tap the three-line menu icon at the top to open the menu.</p>
        <p class="manual-line">Before the Contact section, you can also find “Everything You Need to Know Before You Visit.” It includes information about booking, walk-ins, fees and HMO, lab tests, medical records, certificates, help for seniors and persons with disabilities, and common questions.</p>
        <h5 class="manual-line">1.3 Use the PolyClinic Assistant</h5>
        <p class="manual-line">Select the chatbot or chat button on the website to open the PolyClinic chatbot. It gives four choices:</p>
        <p class="manual-line">Schedule Visit – Book a clinic visit.</p>
        <p class="manual-line">General Information – Ask for basic clinic information.</p>
        <p class="manual-line">Submit Review/Rating – Rate your visit and leave a comment.</p>
        <p class="manual-line">Submit Complaint – Tell the clinic about a problem.</p>
        <p class="manual-line">Helpful tips:</p>
        <p class="manual-line">Type “Menu” to show the four choices again.</p>
        <p class="manual-line">Select or type “Cancel” to stop what you are doing and return to the menu.</p>
        <p class="manual-line">Use the paperclip button to attach a file or picture for the clinic staff.</p>
        <p class="manual-line">Select the “×” at the top-right corner to close the chat.</p>
        <p class="manual-line">If you send messages too quickly, the chat may ask you to wait before sending more.</p>
        <p class="manual-line">To ask a question, select “General Information.” You can see the clinic name, address, email, phone number, and a link to Google Maps. The clinic may also show quick-answer buttons for common questions, such as parking, insurance, or walk-ins. Select a button or type the topic.</p>
        <p class="manual-line">If the chatbot cannot answer your question, it sends your message to the clinic staff and admin. Their reply will appear in the same chat. Keep the chat open or return later to check for their reply.</p>
        <h5 class="manual-line">1.4 Book a Visit</h5>
        <p class="manual-line">Open the chat and select “Schedule Visit.”</p>
        <p class="manual-line">Enter your details: full name, 11-digit contact number (09XXXXXXXXX), date of birth, and a working email address. Temporary or fake email addresses will not be accepted. Keep “Email me my visit confirmation” checked if you want a confirmation sent to your email.</p>
        <p class="manual-line">Choose your visit in this order: select a service, choose a doctor who offers that service, then choose an available date and time.</p>
        <p class="manual-line">Check your details, service, doctor, date, and time. Select “Edit details” or “Change visit” if you need to make changes. When everything is correct, confirm your booking.</p>
        <p class="manual-line">A “Schedule Visit Confirmed” message will appear. Take a screenshot for your records. If you chose email confirmation, check your email too.</p>
        <p class="manual-line">You can select “Back” or “Cancel” at any step.</p>
        <p class="manual-line">If you ask a question while booking, your booking form will pause and your question will be sent to staff. You can select “Continue Schedule Visit” to carry on, “Start Over” to fill out the form again, or “Cancel” to stop the booking.</p>
        <p class="manual-line">Staff cannot see your name while your booking is not yet confirmed. Your question will be connected to your name after the visit is confirmed.</p>
        <h5 class="manual-line">1.5 Leave a Review or Rating</h5>
        <p class="manual-line">After your visit, the clinic will email you a review link. The email includes a code and a QR code.</p>
        <p class="manual-line">Select the link in the email or scan the QR code. You can also open the chat, select “Submit Review/Rating,” and enter the code from your email.</p>
        <p class="manual-line">If you do not want your name shown, check “Post my review anonymously.”</p>
        <p class="manual-line">After your code is accepted, choose a rating from 1 to 5 stars.</p>
        <p class="manual-line">If you give 4 or 5 stars, you can write a comment or type “skip” if you do not want to leave one.</p>
        <p class="manual-line">If you give 3 stars or fewer, you will not be asked to write a comment. The assistant will say sorry and let you know that staff will contact you by email. If the clinic does not have your email, you will be asked to enter one.</p>
        <p class="manual-line">Only one review is allowed for each completed visit. Each review code can be used once. If your code does not work or has already been used, no review will be saved. Contact the clinic if you did not receive a code.</p>
        <p class="manual-line">Positive reviews may appear in the “Patient Voices” section of the website. Your name will be partly hidden (for example, “M**a S.”). If you choose anonymous, it will show “Anonymous Patient.”</p>
        <p class="manual-line">The “Leave Patient Feedback” and “Submit Feedback” buttons also open the chat for you.</p>
        <h5 class="manual-line">1.6 Send a Complaint</h5>
        <p class="manual-line">Open the chat and select “Submit Complaint.”</p>
        <p class="manual-line">Explain your concern in at least one complete sentence.</p>
        <p class="manual-line">The chat will show a “Complaint Recorded” message with a Reference Number. Keep this number in case you need to follow up.</p>
        <p class="manual-line">You can send up to 2 complaints within 24 hours. If you reach the limit, please try again the next day.</p>
        <h5 class="manual-line">1.7 Common Problems and What to Do</h5>
        <p class="manual-line">Chat choices disappeared – Type “Menu” to show them again.</p>
        <p class="manual-line">The chat asks you to choose one of the buttons – Select one of the buttons shown instead of typing a different answer.</p>
        <p class="manual-line">Your email was not accepted – Use your real, active email address. Do not use a temporary or test email.</p>
        <p class="manual-line">You did not receive a confirmation or review email – Check your Spam or Junk folder. If it is not there, contact the clinic. Staff may send it again.</p>
        <p class="manual-line">The chatbot asks you to wait – Wait a few minutes before sending another message.</p>
        <p class="manual-line">A doctor or service is missing – It may not be available or may not have a schedule. Ask clinic staff/admin.</p>
    </details>
    <details class="manual-section">
        <summary>Staff User Manual</summary>
        <h5 class="manual-line">2.1 Sign In</h5>
        <p class="manual-line">Open the clinic website and go to /auth/login. Look for the page named “PolyClinic Staff Login.” The public menu does not have a login button.</p>
        <p class="manual-line">Enter your email address and password, then select “Log In.” If Google sign-in is available, you may use it.</p>
        <p class="manual-line">You will be taken to the Staff Dashboard.</p>
        <p class="manual-line">If your email or password is incorrect, check your details and try again. If your account is turned off, ask the Administrator to turn it back on.</p>
        <p class="manual-line">To reset your password: select “Forgot password?”, enter your email, and select “Send Verification Code.” Enter the 6-digit code sent to your email. The code is valid for 10 minutes. You can ask for another code after 60 seconds. Enter your new password twice, then select “Update Password.” Your new password must have at least 8 characters.</p>
        <p class="manual-line">To sign out, open your profile menu at the top-right and select “Logout.” The website may also sign you out after 30 minutes without activity. You can change this time in Settings → Security.</p>
        <h5 class="manual-line">2.2 Find Your Way Around the Staff Portal</h5>
        <p class="manual-line">The menu on the left has these sections: Dashboard, Scheduled Visits, Chatbot Inquiries, Manage Doctors, and Patient Records.</p>
        <p class="manual-line">At the top, the bell icon shows notifications. You can view all notifications or choose Chatbot, Scheduled Visit, or System. Select a notification to open it, or select “Mark all as read.”</p>
        <p class="manual-line">Open your profile menu to view your name, email, contact number, Staff ID, and role. You can also change your photo, open Settings, or sign out.</p>
        <h5 class="manual-line">2.3 Dashboard</h5>
        <p class="manual-line">The Dashboard gives you a quick view of the clinic for the day.</p>
        <p class="manual-line">It shows today's scheduled visits, questions waiting for a reply, doctors available today, and the number of patients.</p>
        <p class="manual-line">You can also see patient ratings and comments, reported concerns, the status of chatbot questions, popular services, visit trends, the number of questions received, and recent activities.</p>
        <p class="manual-line">Select a reported concern to see more details and the patient comments related to it.</p>
        <h5 class="manual-line">2.4 Scheduled Visits</h5>
        <p class="manual-line">Open “Scheduled Visits” to see patient visits.</p>
        <p class="manual-line">Use the search box to find a patient, doctor, or note. You can filter by doctor, service, or date. Select “Today” to show today's visits, or “Clear” to remove the filters. Select “Download” to save the list as a CSV, Excel, or PDF file.</p>
        <p class="manual-line">The list shows the patient, doctor, service, visit date and time, notes, attendance, and available actions. Select “See more” to load more visits.</p>
        <p class="manual-line">To edit a note: select the three-dot menu (⋮) beside the visit, choose “Edit,” change the note, and select “Save.”</p>
        <p class="manual-line">To change a visit schedule: select ⋮ → “Reschedule,” choose a new date and time from the doctor's available schedule, and confirm. The patient will receive an email with the new details.</p>
        <p class="manual-line">The new schedule must be today or a future date and must be before the session starts. The doctor must be active, and the patient must have a valid email. You cannot choose the same date and time again. If the email is not sent, the schedule change is still saved. Select “Retry email” to try sending it again. Completed visits cannot be rescheduled.</p>
        <p class="manual-line">To record that a patient attended: select “Mark visited” after the patient has come to the clinic. The website records the visit and emails the patient a review code, QR code, and link.</p>
        <p class="manual-line">You can only mark visits scheduled for today or an earlier date. The patient must have a valid email. Add or correct the email in Patient Records if needed.</p>
        <p class="manual-line">If the status says “Visited – Review pass emailed,” the email was sent. If it says “Visited – Email not sent,” the visit was recorded but the email failed. Select “Retry email.” The same review code will be used.</p>
        <h5 class="manual-line">2.5 Chatbot Inquiries</h5>
        <p class="manual-line">Open “Chatbot Inquiries” to see patient questions that the assistant could not answer.</p>
        <p class="manual-line">The list on the left shows recent conversations and whether each one is “In Progress” or “Resolved.” Use the search box or “See More” to find older conversations.</p>
        <p class="manual-line">Select a conversation to open it. The top shows its inquiry number and type. Messages from the patient, chatbot, and staff are marked so you can tell who sent each one. Select an attached picture or file to open it in a new tab.</p>
        <p class="manual-line">Type your reply in the message box and select “Send Reply.” The patient will see your reply in the chat.</p>
        <p class="manual-line">When the question has been answered, select “Mark as Resolve.” You can still reply after an inquiry is marked as resolved. If the patient sends another message, the inquiry will open again.</p>
        <p class="manual-line">If a patient is still booking a visit, their name may not appear until the booking is confirmed. This helps protect their privacy.</p>
        <h5 class="manual-line">2.6 Manage Doctors</h5>
        <p class="manual-line">Staff can view doctor details and update their weekly working hours.</p>
        <p class="manual-line">Open “Manage Doctors.” You can filter the list by department or download it as a CSV, Excel, or PDF file.</p>
        <p class="manual-line">The list shows each doctor's name, specialty, working hours, and status (Available or Unavailable). Select “+ more” to see all working times.</p>
        <p class="manual-line">Select “Edit” beside a doctor. In “Edit Schedule,” check the days the doctor works and enter the start and end times. Select “+ Add session” if the doctor has more than one working time on the same day.</p>
        <p class="manual-line">Select “Save,” then select “Confirm” when asked to save the changes.</p>
        <p class="manual-line">Updated schedules will also appear in the patient chat when they choose an available visit time. Only the Administrator can add, remove, turn on, or turn off doctor profiles.</p>
        <h5 class="manual-line">2.7 Patient Records</h5>
        <p class="manual-line">Open “Patient Records.” Search by patient ID, name, or doctor. You can also filter by department or visit dates, then select “Filter.”</p>
        <p class="manual-line">Select “View” to see the patient's details, most recent department and doctor, and visit history. The history includes the date, service, doctor, and notes or diagnosis. Select “Download” to save the record as a PNG image or PDF.</p>
        <p class="manual-line">Select “Edit” to change the patient's first name, last name, birthday, email, contact number, or notes/diagnosis. You cannot change the Patient ID. Notes are saved under the patient's latest visit. Select “Update Patient” to save.</p>
        <p class="manual-line">Use “Previous” and “Next” to move between pages.</p>
        <h5 class="manual-line">2.8 Settings</h5>
        <p class="manual-line">Open Settings from your profile menu.</p>
        <p class="manual-line">Notifications – Choose whether to receive alerts for new visits and new chatbot questions. You can also choose whether chatbot alerts should be sent to your email.</p>
        <p class="manual-line">Appearance – Turn Dark Mode on or off.</p>
        <p class="manual-line">Profile – View your account details, last sign-in, and role.</p>
        <p class="manual-line">Security – Change your password and choose how long the website can stay open when you are not using it.</p>
        <p class="manual-line">Data &amp; Privacy – Read the Terms &amp; Conditions, download patient information as a CSV file, or clear saved website data.</p>
        <h5 class="manual-line">2.9 Staff Responsibilities</h5>
        <p class="manual-line">When you sign in, you agree to follow the system's Terms &amp; Conditions.</p>
        <p class="manual-line">Keep your password private. Do not share your account with anyone.</p>
        <p class="manual-line">Sign out when you finish, especially if you are using a shared computer.</p>
        <p class="manual-line">Do not copy, take screenshots of, download, or share patient information outside the system or through personal apps and online storage.</p>
        <p class="manual-line">Use the chat and inquiry features only for clinic work.</p>
        <p class="manual-line">The system keeps a record of actions such as sign-ins, chats, and visit updates to help keep information safe and check that the system is being used properly.</p>
        <p class="manual-line">Report any possible data leak or access by an unknown person to the Administrator immediately.</p>
        <h5 class="manual-line">2.10 Common Staff Problems</h5>
        <p class="manual-line">Cannot sign in – Check your email and password. Use “Forgot password?” if needed. If your account is turned off, ask the Administrator.</p>
        <p class="manual-line">Cannot mark a visit as attended – Check that the visit date is today or earlier and that the patient has a valid email. Update the email in Patient Records if needed.</p>
        <p class="manual-line">Visit email was not sent – Select “Retry email.” If it still does not work, tell the Administrator.</p>
        <p class="manual-line">Cannot change a visit schedule – The doctor may be inactive, or the visit may already be completed. Ask the Administrator for help.</p>
        <p class="manual-line">You were signed out – The website may have signed you out because you were not using it. Sign in again or ask about changing the time in Settings.</p>
    </details>
    @if($manualAudience === "admin")
    <details class="manual-section">
        <summary>Administrator User Manual</summary>
        <h5 class="manual-line">3.1 Sign In and Find Your Way Around</h5>
        <p class="manual-line">Go to /auth/login and sign in using your Administrator email and password. You may use Google sign-in if it is available. You will be taken to the Admin Dashboard.</p>
        <p class="manual-line">Password reset, automatic sign-out, and signing out work the same way as they do for Staff.</p>
        <p class="manual-line">The menu on the left includes Dashboard, Chatbot Inquiries, Scheduled Visits, Manage Doctors, Patient Records, Patient Feedback, User Management, System Settings, and Chatbot Commands.</p>
        <p class="manual-line">At the top, use the bell icon to view notifications. Open your profile menu to view Profile, Settings, or Logout.</p>
        <h5 class="manual-line">3.2 Dashboard and Reports</h5>
        <p class="manual-line">The Dashboard shows the number of questions waiting for a reply, today's visits and visits waiting for approval, total patients, and the average patient rating.</p>
        <p class="manual-line">Charts show patient ratings and comments, chatbot question status, the number of visits for each service, visit changes over time, and the number of questions received.</p>
        <p class="manual-line">Select “Diagnostic Analytics” to compare common patient concerns across different time periods and read the reviews related to those concerns. You can also print a report.</p>
        <h5 class="manual-line">3.3 Scheduled Visits</h5>
        <p class="manual-line">The Admin Scheduled Visits page shows one day at a time.</p>
        <p class="manual-line">The top cards show today's visits, the number of patients with bookings, and the doctors involved. Filter visits by doctor, service, or date. Select “Today” to return to the current date.</p>
        <p class="manual-line">Select “Download” to save the list as a PDF, CSV, or Excel file.</p>
        <p class="manual-line">For each visit, you can edit notes, change the schedule, mark the patient as attended, or remove the visit.</p>
        <p class="manual-line">The same rules for changing schedules and marking visits as attended apply to Administrators and Staff. Completed visits cannot be changed to another date or deleted because they are kept to check patient reviews. You can still edit their notes.</p>
        <h5 class="manual-line">3.4 Chatbot Inquiries</h5>
        <p class="manual-line">Open “Chatbot Inquiries” and select a conversation to read.</p>
        <p class="manual-line">Type your answer and select “Send.” The patient will see your reply in the chat.</p>
        <p class="manual-line">When the question has been answered, select “Mark as resolved.” Select “Unresolve” if you need to open it again.</p>
        <p class="manual-line">To remove conversations, check the boxes beside the conversations you want to delete. Check the number selected, then select “Delete.” Confirm when asked. Deleted conversations cannot be brought back.</p>
        <p class="manual-line">New questions also create a notification. An email alert will be sent if email notifications are turned on.</p>
        <h5 class="manual-line">3.5 Manage Doctors</h5>
        <p class="manual-line">Administrators can add, edit, turn on or off, and delete doctor profiles.</p>
        <p class="manual-line">Use the department filter or choose to show all doctors, active doctors only, or inactive doctors only.</p>
        <p class="manual-line">To add a doctor, select “Add New Doctor.” Enter the doctor's full name, one or more services or departments, gender, and at least one working schedule. A contact number, years of experience, education, license, fellowship, description, and photo are optional.</p>
        <p class="manual-line">To add a schedule, choose a day, start time, and end time. The end time must be later than the start time. Select “Add” to include the schedule. A photo must be JPG, PNG, or WEBP and no larger than 2 MB.</p>
        <p class="manual-line">Select “Save Doctor” when finished.</p>
        <p class="manual-line">To update a doctor, select “Edit,” make the changes, and save.</p>
        <p class="manual-line">Turn a doctor to “Inactive” to hide the profile from the website and prevent new bookings. Visits for that doctor cannot be moved to another schedule until the doctor is active again.</p>
        <p class="manual-line">Deleting a doctor also removes the doctor's photo, schedule, and service links. The website will ask you to confirm before deleting.</p>
        <h5 class="manual-line">3.6 Patient Records</h5>
        <p class="manual-line">The top cards show the total number of patients, departments, and doctors involved in patient visits.</p>
        <p class="manual-line">Filter the list by department. Select “Download” to save the list as a PDF, CSV, or Excel file.</p>
        <p class="manual-line">To add a patient, select “Add New Patient.” First name and last name are required. Birthdate, contact number, email, department, doctor, and notes are optional.</p>
        <p class="manual-line">The department and doctor are updated based on the patient's most recent visit. Notes are saved under the most recent visit.</p>
        <p class="manual-line">Select a patient to view their details and full visit history. You can download the record as a PNG image or PDF. Select “Edit” to correct the patient's information.</p>
        <h5 class="manual-line">3.7 Patient Feedback and Ratings</h5>
        <p class="manual-line">Open “Patient Feedback.” You will see three tabs: “Overview,” “Patient Feedback,” and “Complaints.”</p>
        <p class="manual-line">You can filter feedback by rating type (All, Positive, Neutral, or Negative), doctor, service, and date.</p>
        <p class="manual-line">The Overview shows the number of positive, neutral, and negative comments; the average rating out of 5; common problems mentioned in negative comments and complaints; and doctor and service ratings.</p>
        <p class="manual-line">The Patient Feedback tab shows each review's rating, comment, doctor, service, rating type, and date. The Complaints tab shows the complaint, concern, reported reason, and date.</p>
        <p class="manual-line">You can download feedback and complaint lists as PDF, CSV, or Excel files.</p>
        <p class="manual-line">The website checks patient reviews automatically and sorts them as positive, neutral, or negative. Only reviews marked positive can appear in the public “Patient Voices” section.</p>
        <p class="manual-line">Patients who give 3 stars or fewer are told that staff will contact them by email. Please follow up with these patients.</p>
        <h5 class="manual-line">3.8 User Management</h5>
        <p class="manual-line">Use “User Management” to create and manage Staff and Administrator accounts.</p>
        <p class="manual-line">For safety, you must confirm your identity once every 24 hours before using this section or making changes.</p>
        <p class="manual-line">When the “Verify your identity” window appears, select “Send OTP.” A one-time code will be sent to your Administrator email. The code is valid for 10 minutes.</p>
        <p class="manual-line">Enter the code and select “Verify &amp; Continue.” If you enter the wrong code 5 times, request a new one.</p>
        <p class="manual-line">After successful confirmation, you can manage accounts for 24 hours in that sign-in session.</p>
        <p class="manual-line">To create an account, select “Add New Staff Account.” Enter the person's full name, email address, role (Administrator or Staff), password, and account status (Active or Inactive). The email must not already be used. The password must have at least 6 characters. Select “Save Account.”</p>
        <p class="manual-line">You can filter accounts by role or status. Each row shows the person's name, email, role, status, and last sign-in.</p>
        <p class="manual-line">Select “Edit” to change account details. Leave the password field empty if you do not want to change the password.</p>
        <p class="manual-line">Turn an account to “Inactive” to stop that person from signing in. Select “Delete” to permanently remove an account. You cannot delete your own account.</p>
        <p class="manual-line">Send new account passwords safely. Ask the user to change the password in Settings → Security after signing in for the first time.</p>
        <h5 class="manual-line">3.9 System Settings – Website Information</h5>
        <p class="manual-line">Changes in System Settings appear on the public website and may also change the chatbot's answers. There are five tabs.</p>
        <p class="manual-line">Clinic Hours – Enter the clinic's opening hours, then select “Save Hours.”</p>
        <p class="manual-line">About – Update the clinic description, Mission, Vision, and Core Values. Use “+ Add Core Value” to add one or the “×” to remove one. Select “Save About.”</p>
        <p class="manual-line">Contact – Update the clinic address, phone number, email, Facebook link, and website. Select “Save Contact.”</p>
        <p class="manual-line">Services – Select “Add Service” to add a service or choose an existing service to edit. Enter its name, room, and description. You can also add focus areas, choose an icon and card color, and add a picture. Pictures must be JPG, PNG, or WEBP and no larger than 2 MB. Leave the icon and color on “Automatic” to let the website choose them. Turn off “Available” to hide the service from the website and chatbot.</p>
        <p class="manual-line">FAQs – Select “Add FAQ,” enter the question and answer, and check “Show on the website” if you want patients to see it. Use the move controls to change the order. You can edit or delete questions and answers anytime.</p>
        <h5 class="manual-line">3.10 Chatbot Quick Answers</h5>
        <p class="manual-line">Chatbot Commands let you add quick answers for questions the assistant can answer without sending them to staff. Examples include parking, insurance, and walk-in visits.</p>
        <p class="manual-line">Open “Chatbot Commands” and select “Add Command.”</p>
        <p class="manual-line">Enter the button name that patients will see, such as “Parking Info.”</p>
        <p class="manual-line">Enter words or phrases patients might type. Press Enter or use a comma after each one.</p>
        <p class="manual-line">Write the answer the chatbot should send.</p>
        <p class="manual-line">Check “Active” to turn the quick answer on, then select “Save.”</p>
        <p class="manual-line">Select “Edit” to change a quick answer. Select “Delete” and confirm to remove one. The list shows 7 items at a time. Select “See more” to view the rest.</p>
        <h5 class="manual-line">3.11 Notifications, Profile, and Settings</h5>
        <p class="manual-line">Notifications – View alerts for chatbot questions, scheduled visits, feedback, and system updates. Select “Mark read” for one alert or “Mark all as read” for all alerts.</p>
        <p class="manual-line">Profile – Update your photo, name, email, and birthday. Select “Save Changes” when finished.</p>
        <p class="manual-line">Settings – Choose which alerts you want to receive, including new visits, chatbot questions, complaints, and patient feedback. You can also turn email alerts for chatbot questions on or off.</p>
        <p class="manual-line">Appearance – Turn Dark Mode on or off.</p>
        <p class="manual-line">Security – Change your password and choose when the website should sign you out after you stop using it: 15 minutes, 30 minutes, 1 hour, or Never.</p>
        <p class="manual-line">Data &amp; Privacy – Read the Terms &amp; Conditions, download patient information as a CSV file, or clear saved website data.</p>
        <p class="manual-line">Profile details – View your account information, last sign-in, and role.</p>
        <h5 class="manual-line">3.12 Good Practices for Administrators</h5>
        <p class="manual-line">Give each person their own account. Turn off accounts for people who no longer work at the clinic.</p>
        <p class="manual-line">Keep doctor schedules, services, clinic hours, and contact details up to date. Patients can see these changes on the website.</p>
        <p class="manual-line">Check new chatbot questions and questions waiting for a reply every day. Mark conversations as resolved when they are finished.</p>
        <p class="manual-line">Check common negative comments regularly and contact patients who gave low ratings.</p>
        <p class="manual-line">Download patient information only when you are allowed to do so. Keep downloaded files in a safe place.</p>
        <p class="manual-line">Make sure clinic emails are working. The website uses email to send visit confirmations, review codes, schedule changes, password reset codes, and account verification codes.</p>
        <h5 class="manual-line">3.13 Common Administrator Problems</h5>
        <p class="manual-line">A doctor is missing from the website or booking list – Check that the doctor is Active, has at least one working schedule, and is linked to a service that is Available.</p>
        <p class="manual-line">A service is missing from the chatbot – Check that the service is Available and has at least one active doctor with a schedule.</p>
        <p class="manual-line">User Management asks you to confirm your identity again – Your 24-hour access has ended. Send and enter a new one-time code.</p>
        <p class="manual-line">You did not receive a code or other email – Check your Spam or Junk folder. If the email is still missing, ask the person who manages the website server to check the email settings.</p>
        <p class="manual-line">You cannot delete a completed visit – Completed visits are kept so the clinic can check patient reviews.</p>
        <p class="manual-line">A patient did not receive a review code – Check that the visit was marked as attended and that the email was sent. Select “Retry email” if needed.</p>
        <p class="manual-line">Website information still looks old – Save the changes in System Settings and refresh the website. If it still looks the same, go to Settings → Data &amp; Privacy → Clear cached data.</p>
    </details>
    @endif
    <details class="manual-section">
        <summary>Quick Reference</summary>
        <p class="manual-line">Book a visit – Patient: Chatbot → Schedule Visit</p>
        <p class="manual-line">Leave a review – Patient: Email link, or Chatbot → Submit Review/Rating</p>
        <p class="manual-line">Send a complaint – Patient: Chatbot → Submit Complaint</p>
        <p class="manual-line">Show chat choices again – Patient: Type “Menu” in the chat</p>
        <p class="manual-line">Mark a visit as attended – Staff/Administrator: Scheduled Visits → Mark visited</p>
        <p class="manual-line">Change a visit schedule – Staff/Administrator: Scheduled Visits → ⋮ → Reschedule</p>
        <p class="manual-line">Reply to a patient question – Staff/Administrator: Chatbot Inquiries</p>
        <p class="manual-line">Change a doctor's working hours – Staff/Administrator: Manage Doctors → Edit</p>
        @if($manualAudience === "admin")
        <p class="manual-line">Add or remove a doctor – Administrator: Manage Doctors</p>
        <p class="manual-line">Create a Staff account – Administrator: User Management</p>
        <p class="manual-line">Change website information, hours, services, or FAQs – Administrator: System Settings</p>
        <p class="manual-line">Add a chatbot quick answer – Administrator: Chatbot Commands</p>
        <p class="manual-line">View patient ratings and common concerns – Administrator: Patient Feedback or Dashboard → Diagnostic Analytics</p>
        @endif
    </details>
</div>
<style>
    .settings-user-manual { line-height: 1.55; overflow-wrap: anywhere; }
    .settings-user-manual > p + p { margin-top: 0.4rem; }
    .manual-section { border-top: 1px solid #c7ccd2; margin-top: 0.75rem; padding-top: 0.75rem; }
    .manual-section summary { cursor: pointer; font-weight: 600; }
    .manual-section .manual-line { margin: 0.45rem 0 0; font-size: 0.875rem; }
    .manual-section h5.manual-line { margin-top: 1rem; font-weight: 700; }
</style>
@else
<form id="accountLogoutForm" method="POST" action="{{ route('logout') }}" hidden>
    @csrf
</form>
<script>
(() => {
    const csrf = @json(csrf_token());
    const loginUrl = @json(route('login'));
    const activityUrl = @json(route($accountRole . '.profile.activity'));
    const activityKey = 'opti_account_activity_' + @json((string) Auth::id());
    let timeout = @json((int) (Auth::user()->session_timeout ?? 30));
    let lastActivity = @json((int) session('account_last_activity', now()->timestamp)) * 1000;
    let lastSent = 0;
    let pending = null;
    let busy = false;
    let expired = false;

    function publishActivity() {
        try { localStorage.setItem(activityKey, JSON.stringify({ lastActivity, timeout })); } catch (_) {}
    }
    publishActivity();
    window.addEventListener('storage', event => {
        if (event.key !== activityKey || !event.newValue) return;
        try {
            const state = JSON.parse(event.newValue);
            lastActivity = Math.max(lastActivity, state.lastActivity);
            timeout = state.timeout;
        } catch (_) {}
    });

    async function sendActivity() {
        pending = null;
        if (busy || expired) return;
        busy = true;
        lastSent = Date.now();
        try {
            const response = await fetch(activityUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            if (response.status === 401 || response.status === 419) {
                expired = true;
                window.location.assign(loginUrl);
                return;
            }
            if (!response.ok) return;
            const state = await response.json();
            timeout = state.timeout;
            publishActivity();
        } catch (_) {
            // Retry on the next interaction; do not silently extend server activity.
        } finally { busy = false; }
    }

    function recordActivity(event) {
        if (!event.isTrusted || expired) return;
        lastActivity = Date.now();
        publishActivity();
        if (Date.now() - lastSent >= 10000) {
            if (pending) clearTimeout(pending);
            sendActivity();
        } else if (!pending) {
            pending = setTimeout(sendActivity, 10000 - (Date.now() - lastSent));
        }
    }
    ['pointerdown', 'keydown', 'wheel', 'touchstart'].forEach(type =>
        document.addEventListener(type, recordActivity, { passive: true }));

    function checkExpiry() {
        if (expired || timeout === 0 || Date.now() - lastActivity < timeout * 60000) return;
        expired = true;
        document.getElementById('accountLogoutForm').submit();
    }
    setInterval(checkExpiry, 1000);
    document.addEventListener('visibilitychange', checkExpiry);

    document.querySelectorAll('[data-account-clear-cache]').forEach(button => {
        button.addEventListener('click', async () => {
            const status = document.querySelector('[data-account-cache-status]');
            button.disabled = true;
            status.textContent = 'Clearing cached pages…';
            try {
                const response = await fetch(button.dataset.cacheUrl, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Could not clear cached pages. Please try again.');
                status.textContent = result.message;
            } catch (error) {
                status.textContent = error.message || 'Could not clear cached pages. Please try again.';
            } finally { button.disabled = false; }
        });
    });
})();
</script>
@endisset
