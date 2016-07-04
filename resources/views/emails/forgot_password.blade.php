Hello,<br/><br/>

You have requested for a password reset for your account.
To complete the process, click the link below. <br/>
<br/>
<?php $url = env('WEB_URL'). 'password/reset/'.$token; ?>
<a href="{{$url}}">Click here to reset your password.</a>
<br/><br/>
If you did not ask to change your password, then you can ignore this email and your password will not be changed.
The link above will remain active for 1 hour.
<br/><br/>
The Lineup Beast<br/><br/>
<a href="{{config('app.url')}}">{{config('app.url')}}</a>