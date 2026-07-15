<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Your Qyzen Account</title>
</head>
<body style="margin:0;background:#f4f4f5;padding:40px 16px;font-family:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;color:#18181b;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;border-collapse:collapse;">

          <!-- Brand -->
          <tr>
            <td style="padding:0 4px 20px;">
              <span style="font-size:15px;font-weight:600;letter-spacing:-.01em;color:#18181b;">Qyzen</span>
            </td>
          </tr>

          <!-- Card -->
          <tr>
            <td style="background:#ffffff;border:1px solid #e4e4e7;border-radius:16px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">

                <tr>
                  <td style="padding:40px 40px 0;">
                    <div style="font-family:ui-monospace,'Cascadia Code',Menlo,Consolas,monospace;font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:#8a8a94;">Account access</div>
                    <h1 style="margin:16px 0 0;font-size:26px;line-height:1.25;font-weight:600;letter-spacing:-.02em;color:#18181b;">Your account is ready.</h1>
                  </td>
                </tr>

                <tr>
                  <td style="padding:18px 40px 0;">
                    <p style="margin:0;font-size:15px;line-height:1.65;color:#52525b;">
                      Good day, {{ $user->given_name }} {{ $user->surname }}. <strong style="color:#18181b;font-weight:600;">{{ $createdBy }}</strong> created a Qyzen account for you. Use the verification code below, then choose your password.
                    </p>
                  </td>
                </tr>

                <!-- Verification -->
                <tr>
                  <td style="padding:28px 40px 0;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #e4e4e7;border-radius:12px;">
                      <tr>
                        <td style="padding:20px 22px 6px;font-family:ui-monospace,'Cascadia Code',Menlo,Consolas,monospace;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#8a8a94;">Email address</td>
                      </tr>
                      <tr>
                        <td style="padding:0 22px 18px;font-size:16px;font-weight:600;color:#18181b;word-break:break-word;">{{ $user->email }}</td>
                      </tr>
                      <tr>
                        <td style="padding:0 22px;"><div style="border-top:1px solid #e4e4e7;height:1px;line-height:1px;font-size:0;">&nbsp;</div></td>
                      </tr>
                      <tr>
                        <td style="padding:18px 22px 6px;font-family:ui-monospace,'Cascadia Code',Menlo,Consolas,monospace;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#8a8a94;">Verification code</td>
                      </tr>
                      <tr>
                        <td style="padding:0 22px 20px;font-family:ui-monospace,'Cascadia Code',Menlo,Consolas,monospace;font-size:24px;font-weight:600;letter-spacing:.18em;color:#18181b;word-break:break-all;">{{ $verificationCode }}</td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- CTA -->
                <tr>
                  <td style="padding:28px 40px 0;">
                    <a href="{{ $confirmUrl }}" style="display:inline-block;background:#18181b;color:#ffffff;text-decoration:none;padding:13px 28px;border-radius:9999px;font-size:14px;font-weight:600;">
                      Verify Account
                    </a>
                  </td>
                </tr>

                <tr>
                  <td style="padding:24px 40px 40px;">
                    <p style="margin:0 0 14px;font-size:13px;line-height:1.6;color:#52525b;">
                      This code and link expire in seven days.
                    </p>
                    <p style="margin:0 0 6px;font-size:13px;line-height:1.6;color:#8a8a94;">
                      If the button doesn't work, paste this link into your browser:
                    </p>
                    <a href="{{ $confirmUrl }}" style="font-size:13px;line-height:1.6;color:#4f7bff;text-decoration:none;word-break:break-all;">{{ $confirmUrl }}</a>
                  </td>
                </tr>

              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding:20px 4px 0;font-size:12px;line-height:1.5;color:#a1a1aa;">
              {{ date('Y') }} © Qyzen · This notice was sent to {{ $user->email }}.
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
