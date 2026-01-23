<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Welcome to {{ config('app.name') }}!</title>

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
    <div style="background-color:#4a76a8; color:#ffffff; padding:20px; text-align:center;">
      <h1 style="margin:0; font-family:Arial,Helvetica,sans-serif;">
        Welcome to {{ config('app.name') }}!
      </h1>
    </div>

    <!-- Content -->
    <div style="padding:20px; background-color:#f9f9f9; font-family:Arial,Helvetica,sans-serif; color:#333333; line-height:1.6;">

      <p style="margin:0 0 16px;">Hello {{ $user->name }},</p>

      <p style="margin:0 0 16px;">
        Your account has been successfully created on <strong>{{ config('app.name') }}</strong> by {{ $creator->name }}.
        You can now log in and start using the platform right away.
      </p>

      <p style="margin:0 0 16px;">
        Here are your login details:
      </p>

      <p style="margin:0 0 16px;">
        📧 <strong>Email:</strong> {{ $user->email }}<br>
        🔑 <strong>Temporary Password:</strong> {{ $password }}
      </p>

      <p style="margin:0 0 16px;">
        For security reasons, we recommend changing your password after your first login.
      </p>

      @php
        $loginUrl = route('login');
      @endphp

      <table role="presentation" align="center" cellpadding="0" cellspacing="0" style="margin:20px auto;">
        <tr>
          <td align="center" bgcolor="#4a76a8" style="border-radius:6px;">
            <!--[if mso]>
            <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml"
              href="{{ $loginUrl }}"
              style="height:44px; v-text-anchor:middle; width:260px;"
              arcsize="8%" stroke="f" fillcolor="#4a76a8">
              <w:anchorlock/>
              <center style="color:#ffffff; font-family:Arial,Helvetica,sans-serif; font-size:16px; font-weight:600;">
                Login to Your Account
              </center>
            </v:roundrect>
            <![endif]-->

            <!--[if !mso]><!-- -->
            <a href="{{ $loginUrl }}"
               target="_blank"
               style="background-color:#4a76a8; border:1px solid #4a76a8; border-radius:6px;
                      color:#ffffff !important; text-decoration:none !important;
                      display:inline-block; padding:12px 22px; font-family:Arial,Helvetica,sans-serif;
                      font-size:14px; font-weight:600;">
              Login to Your Account
            </a>
            <!--<![endif]-->
          </td>
        </tr>
      </table>

      <p style="margin:0 0 6px;">
        If the button doesn’t work, copy and paste this link into your browser:
      </p>

      <p style="margin:0 0 16px; word-break:break-all;">
        <a href="{{ $loginUrl }}" target="_blank" style="color:#4a76a8;">
          {{ $loginUrl }}
        </a>
      </p>

      <p style="margin:0;">
        Best regards,<br>
        {{ config('app.name') }} Team
      </p>

      <p style="margin:8px 0 0;">
        <a href="{{ config('app.url') }}">{{ config('app.name') }}</a>
      </p>
    </div>

    <!-- Footer -->
    <div style="text-align:center; padding:10px; font-size:12px; color:#777777; font-family:Arial,Helvetica,sans-serif;">
      <p style="margin:0;">
        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
      </p>
    </div>

  </div>
</body>
</html>
