<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Welcome to {{config('app.name')}}!</title>

  <meta name="color-scheme" content="light">
  <meta name="supported-color-schemes" content="light dark">
  <style>
    body { margin:0; padding:0; background:#ffffff; }
    .container { max-width:600px; margin:0 auto; }
  </style>
</head>
<body style="margin:0; padding:0; background:#ffffff;">
  <div class="container" style="max-width:600px; margin:0 auto;">
    <!-- Header -->
    <div class="header" style="background-color:#4a76a8; color:#ffffff; padding:20px; text-align:center;">
      <h1 style="margin:0; font-family:Arial,Helvetica,sans-serif;">Welcome to {{config('app.name')}}!</h1>
    </div>

    <!-- Content -->
    <div class="content" style="padding:20px; background-color:#f9f9f9; font-family:Arial,Helvetica,sans-serif; color:#333333; line-height:1.6;">
      <p style="margin:0 0 16px;">Hello {{ $user->name }},</p>
      <p style="margin:0 0 16px;">
        We’re excited to welcome you to {{config('app.name')}}!<br/>
        Your account has been successfully created, and you’re now ready to use .
      </p>


    @if (!empty($password))
        <p style="margin:0 0 16px;">
        Here are your login details:
        <br/>
    📧 Email: {{ $user->email }}
        <br/>
 🔑    Password: {{ $password }}
    </p>

    @endif

    @if($user->user_type !== 'affiliate')
        <!-- Bulletproof button -->
        <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" style="margin:20px auto;">
            <tr>
            <td align="center" bgcolor="#4a76a8" style="border-radius:6px;">
                <!--[if mso]>
                <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="{{ $loginUrl }}"
                style="height:44px; v-text-anchor:middle; width:260px;" arcsize="8%" stroke="f" fillcolor="#4a76a8">
                <w:anchorlock/>
                <center style="color:#ffffff; font-family:Arial,Helvetica,sans-serif; font-size:16px; font-weight:600;">
                    Verify Email Address
                </center>
                </v:roundrect>
                <![endif]-->
                <!--[if !mso]><!-- -->
                <a href="{{ $loginUrl }}"
                target="_blank"
                style="background-color:#4a76a8; border:1px solid #4a76a8; border-radius:6px;
                        color:#ffffff !important; text-decoration:none !important;
                        display:inline-block; padding:12px 22px; font-family:Arial,Helvetica,sans-serif;
                        font-size:14px; font-weight:600; line-height:100%; mso-line-height-rule:exactly;">
                <span style="color:#ffffff !important; text-decoration:none !important;">Login to Your Account</span>
                </a>
                <!--<![endif]-->
            </td>
            </tr>
        </table>

        <p style="margin:0 0 6px;">If you're having trouble clicking the button, copy and paste this URL into your web browser:</p>
        <p style="margin:0 0 16px; word-break:break-all;">
            <a href="{{ $loginUrl }}" target="_blank"
            style="color:#4a76a8; text-decoration:underline; font-family:Arial,Helvetica,sans-serif;">
            {{ $loginUrl }}
            </a>
        </p>
      @endif

      <p style="margin:0;">Regards,<br>The LifeRise Solutions Team</p>
      <a href="{{ config('app.url') }}">{{ config('app.name') }}</a>
    </div>

    <!-- Footer -->
    <div class="footer" style="text-align:center; padding:10px; font-size:12px; color:#777777; font-family:Arial,Helvetica,sans-serif;">
      <p style="margin:0;">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
  </div>
</body>
</html>
