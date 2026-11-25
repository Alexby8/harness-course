<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подтверждение email — BotGate</title>
</head>
<body style="margin:0;padding:0;background-color:#030712;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#030712;min-height:100vh;">
    <tr>
        <td align="center" style="padding:48px 16px;">

            {{-- Card --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background-color:#111827;border:1px solid #1f2937;border-radius:16px;overflow:hidden;">

                {{-- Header --}}
                <tr>
                    <td style="background-color:#0f172a;border-bottom:1px solid #1f2937;padding:24px 36px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>
                                    <span style="font-size:20px;font-weight:700;letter-spacing:-0.5px;color:#ffffff;">Bot</span><span style="font-size:20px;font-weight:700;letter-spacing:-0.5px;color:#818cf8;">Gate</span>
                                </td>
                                <td align="right">
                                    <span style="font-size:11px;color:#4b5563;font-weight:500;letter-spacing:0.5px;text-transform:uppercase;">Прокси для Telegram Bot API</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td style="padding:36px 36px 28px;">

                        {{-- Icon --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                            <tr>
                                <td align="center">
                                    <div style="display:inline-block;width:56px;height:56px;background-color:#1e1b4b;border:1px solid #3730a3;border-radius:14px;text-align:center;line-height:56px;font-size:26px;">✉️</div>
                                </td>
                            </tr>
                        </table>

                        <h1 style="margin:0 0 12px;font-size:22px;font-weight:700;color:#f1f5f9;text-align:center;letter-spacing:-0.3px;">
                            Подтвердите ваш email
                        </h1>

                        <p style="margin:0 0 8px;font-size:14px;color:#6b7280;text-align:center;line-height:1.6;">
                            Для завершения регистрации в BotGate нажмите кнопку ниже.
                        </p>
                        <p style="margin:0 0 28px;font-size:13px;color:#4b5563;text-align:center;">
                            {{ $email }}
                        </p>

                        {{-- CTA Button --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                            <tr>
                                <td align="center">
                                    <a href="{{ $url }}"
                                       style="display:inline-block;background-color:#4f46e5;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;padding:13px 32px;border-radius:10px;letter-spacing:0.1px;">
                                        Подтвердить email →
                                    </a>
                                </td>
                            </tr>
                        </table>

                        {{-- Divider --}}
                        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
                            <tr>
                                <td style="border-top:1px solid #1f2937;"></td>
                            </tr>
                        </table>

                        <p style="margin:0 0 6px;font-size:12px;color:#4b5563;text-align:center;">
                            Если кнопка не работает, скопируйте ссылку в браузер:
                        </p>
                        <p style="margin:0;font-size:11px;color:#374151;text-align:center;word-break:break-all;font-family:'Courier New',Courier,monospace;">
                            {{ $url }}
                        </p>

                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background-color:#0d1117;border-top:1px solid #1f2937;padding:18px 36px;">
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>
                                    <p style="margin:0;font-size:11px;color:#374151;line-height:1.6;">
                                        Если вы не регистрировались в BotGate — просто проигнорируйте это письмо.
                                        Ссылка действует <strong style="color:#4b5563;">24 часа</strong>.
                                    </p>
                                </td>
                                <td align="right" style="padding-left:16px;white-space:nowrap;">
                                    <span style="font-size:11px;color:#1f2937;font-weight:700;">Bot<span style="color:#312e81;">Gate</span></span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
