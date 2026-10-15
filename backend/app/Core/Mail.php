<?php

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mail
{
    private static array $config = [];

    public static function init(array $config = []): void
    {
        self::$config = $config;
    }

    private static function getConfig(string $key, string $default = ''): string
    {
        // 1. Revisa si viene en $config
        if (!empty(self::$config[$key])) {
            return (string)self::$config[$key];
        }

        // 2. Mapeo de nombres de variables en .env
        $envMap = [
            'host'       => 'MAIL_HOST',
            'port'       => 'MAIL_PORT',
            'encryption' => 'MAIL_ENCRYPTION',
            'username'   => 'MAIL_USERNAME',
            'password'   => 'MAIL_PASSWORD',
            'from_email' => 'MAIL_FROM_ADDRESS',
            'from_name'  => 'MAIL_FROM_NAME',
        ];

        $envKey = $envMap[$key] ?? strtoupper($key);

        // 3. Busca en getenv, $_ENV o $_SERVER
        $val = getenv($envKey) ?: ($_ENV[$envKey] ?? $_SERVER[$envKey] ?? null);

        if ($val !== null && $val !== '') {
            return (string)$val;
        }

        // Fallback especial para from_email si no se definió
        if ($key === 'from_email') {
            return self::getConfig('username');
        }

        return $default;
    }

    public static function sendMagicLink(string $toEmail, string $userName, string $magicLink): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Obtener variables de forma ultra-segura
            $host       = self::getConfig('host', 'smtp.gmail.com');
            $port       = (int)self::getConfig('port', '587');
            $encryption = self::getConfig('encryption', 'tls');
            $username   = self::getConfig('username');
            $password   = self::getConfig('password');
            $fromEmail  = self::getConfig('from_email');
            $fromName   = self::getConfig('from_name', 'Gacela Conecta');

            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $username;
            $mail->Password   = $password;
            $mail->SMTPSecure = ($encryption === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $port;
            $mail->CharSet    = 'UTF-8';

            // Remitente y Destinatario
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $userName);

            // Contenido del Correo
            $mail->isHTML(true);
            $mail->Subject = 'Confirmación de acceso | Gacela Conecta';

            $bannerPath = __DIR__ . '/../../public/gacela-access-banner.png';

            $mail->addEmbeddedImage(
                $bannerPath,
                'gacela_access_banner',
                'gacela-access-banner.png',
                'base64',
                'image/png'
            );

            $mail->Body = "
            <!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Gacela Conecta | Confirmación de acceso</title>
            </head>

            <body style='
                margin:0;
                padding:0;
                background-color:#f0edec;
                font-family:Arial, Helvetica, sans-serif;
                color:#1c1b1b;
            '>

            <table
                width='100%'
                cellpadding='0'
                cellspacing='0'
                border='0'
                style='
                    background-color:#f0edec;
                    margin:0;
                    padding:40px 15px;
                '
            >
                <tr>
                    <td align='center'>

                        <!-- CONTENEDOR PRINCIPAL -->
                        <table
                            width='640'
                            cellpadding='0'
                            cellspacing='0'
                            border='0'
                            style='
                                width:100%;
                                max-width:640px;
                                background-color:#ffffff;
                                border-radius:12px;
                                overflow:hidden;
                                border:1px solid #e5e2e1;
                                box-shadow:0 16px 24px -8px rgba(0,0,0,0.08);
                            '
                        >

                            <!-- BARRA CORPORATIVA -->
                            <tr>
                                <td style='padding:0;'>
                                    <table
                                        width='100%'
                                        cellpadding='0'
                                        cellspacing='0'
                                        border='0'
                                    >
                                        <tr>
                                            <td
                                                width='33.33%'
                                                style='
                                                    height:6px;
                                                    background-color:#f58634;
                                                    font-size:0;
                                                    line-height:0;
                                                '
                                            >&nbsp;</td>

                                            <td
                                                width='33.33%'
                                                style='
                                                    height:6px;
                                                    background-color:#1b6eb8;
                                                    font-size:0;
                                                    line-height:0;
                                                '
                                            >&nbsp;</td>

                                            <td
                                                width='33.33%'
                                                style='
                                                    height:6px;
                                                    background-color:#ad130b;
                                                    font-size:0;
                                                    line-height:0;
                                                '
                                            >&nbsp;</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>


                            <!-- HEADER -->
                            <tr>
                                <td
                                    style='
                                        padding:32px 40px 26px;
                                        background-color:#ffffff;
                                        border-bottom:1px solid #e5e2e1;
                                    '
                                >

                                    <table
                                        width='100%'
                                        cellpadding='0'
                                        cellspacing='0'
                                        border='0'
                                    >
                                        <tr>

                                            <td align='left'>

                                                <div style='
                                                    font-size:25px;
                                                    line-height:32px;
                                                    font-weight:800;
                                                    letter-spacing:-0.5px;
                                                    color:#005595;
                                                '>
                                                    Gacela Conecta
                                                </div>

                                                <div style='
                                                    margin-top:5px;
                                                    font-size:12px;
                                                    line-height:18px;
                                                    color:#717782;
                                                '>
                                                    Plataforma corporativa
                                                </div>

                                            </td>

                                            <td
                                                align='right'
                                                valign='middle'
                                                style='
                                                    font-size:24px;
                                                    color:#005595;
                                                '
                                            >
                                                🔔
                                            </td>

                                        </tr>
                                    </table>

                                </td>
                            </tr>


                            <!-- HERO -->
                            <tr>
                                <td
                                    style='
                                        background-color:#f6f3f2;
                                        padding:38px 40px 32px;
                                        text-align:center;
                                    '
                                >

                                    <img
                                    src='cid:gacela_access_banner'
                                    alt='Gacela Conecta'
                                    width='560'
                                    style='
                                        display:block;
                                        width:100%;
                                        max-width:560px;
                                        height:auto;
                                        border:0;
                                        border-radius:10px;
                                        margin:0 auto 28px;
                                    '
                                    >

                                    <h1 style='
                                        margin:0;
                                        color:#1c1b1b;
                                        font-size:25px;
                                        line-height:34px;
                                        font-weight:700;
                                    '>
                                        Confirmación de acceso
                                    </h1>

                                </td>
                            </tr>


                            <!-- CONTENIDO -->
                            <tr>
                                <td
                                    style='
                                        padding:36px 40px 20px;
                                        background-color:#ffffff;
                                    '
                                >

                                    <p style='
                                        margin:0 0 18px;
                                        font-size:16px;
                                        line-height:25px;
                                        color:#414751;
                                    '>
                                        Hola, <strong style='color:#1c1b1b;'>{$userName}</strong>.
                                    </p>

                                    <p style='
                                        margin:0 0 24px;
                                        font-size:16px;
                                        line-height:25px;
                                        color:#414751;
                                    '>
                                        Hemos recibido una solicitud de acceso a tu cuenta
                                        de <strong style='color:#005595;'>Gacela Conecta</strong>.
                                        Por motivos de seguridad, utiliza el siguiente enlace
                                        para confirmar tu identidad e ingresar a la plataforma.
                                    </p>


                                    <!-- AVISO DE EXPIRACIÓN -->
                                    <table
                                        width='100%'
                                        cellpadding='0'
                                        cellspacing='0'
                                        border='0'
                                        style='
                                            margin:0 0 30px;
                                            background-color:#f6f3f2;
                                            border-left:4px solid #005595;
                                            border-radius:6px;
                                        '
                                    >
                                        <tr>
                                            <td
                                                width='45'
                                                valign='top'
                                                style='
                                                    padding:16px 8px 16px 16px;
                                                    font-size:20px;
                                                '
                                            >
                                                ⏱️
                                            </td>

                                            <td
                                                valign='middle'
                                                style='
                                                    padding:14px 16px 14px 4px;
                                                '
                                            >

                                                <div style='
                                                    margin-bottom:4px;
                                                    font-size:14px;
                                                    line-height:20px;
                                                    font-weight:700;
                                                    color:#1c1b1b;
                                                '>
                                                    Acceso de seguridad temporal
                                                </div>

                                                <div style='
                                                    font-size:12px;
                                                    line-height:18px;
                                                    color:#717782;
                                                '>
                                                    Este enlace expirará automáticamente
                                                    en
                                                    <strong style='color:#a80e08;'>
                                                        15 minutos
                                                    </strong>.
                                                </div>

                                            </td>
                                        </tr>
                                    </table>


                                    <!-- BOTÓN -->
                                    <table
                                        width='100%'
                                        cellpadding='0'
                                        cellspacing='0'
                                        border='0'
                                    >
                                        <tr>
                                            <td align='center'>

                                                <a
                                                    href='{$magicLink}'
                                                    style='
                                                        display:inline-block;
                                                        background-color:#005595;
                                                        color:#ffffff;
                                                        text-decoration:none;
                                                        padding:15px 34px;
                                                        border-radius:8px;
                                                        font-size:14px;
                                                        line-height:20px;
                                                        font-weight:700;
                                                        box-shadow:0 8px 18px rgba(0,85,149,0.20);
                                                    '
                                                >
                                                    Confirmar mi identidad
                                                </a>

                                            </td>
                                        </tr>
                                    </table>


                                    <!-- ENLACE ALTERNATIVO -->
                                    <p style='
                                        margin:24px 0 0;
                                        text-align:center;
                                        font-size:11px;
                                        line-height:17px;
                                        color:#717782;
                                    '>
                                        Si el botón no funciona, copia y pega el siguiente
                                        enlace en tu navegador:
                                    </p>

                                    <p style='
                                        margin:8px 0 0;
                                        text-align:center;
                                        font-size:11px;
                                        line-height:17px;
                                        word-break:break-all;
                                    '>
                                        <a
                                            href='{$magicLink}'
                                            style='
                                                color:#1b6eb8;
                                                text-decoration:none;
                                            '
                                        >
                                            {$magicLink}
                                        </a>
                                    </p>

                                </td>
                            </tr>


                            <!-- DIVISOR -->
                            <tr>
                                <td style='padding:10px 40px 0;'>
                                    <div style='
                                        height:1px;
                                        background-color:#e5e2e1;
                                        font-size:0;
                                        line-height:0;
                                    '>
                                        &nbsp;
                                    </div>
                                </td>
                            </tr>


                            <!-- SEGURIDAD -->
                            <tr>
                                <td
                                    style='
                                        padding:28px 40px 32px;
                                        background-color:#ffffff;
                                    '
                                >

                                    <table
                                        width='100%'
                                        cellpadding='0'
                                        cellspacing='0'
                                        border='0'
                                    >
                                        <tr>

                                            <td
                                                width='50%'
                                                valign='top'
                                                style='padding-right:10px;'
                                            >

                                                <table
                                                    cellpadding='0'
                                                    cellspacing='0'
                                                    border='0'
                                                >
                                                    <tr>

                                                        <td
                                                            width='42'
                                                            valign='top'
                                                            style='
                                                                padding-right:12px;
                                                            '
                                                        >
                                                            <div style='
                                                                width:40px;
                                                                height:40px;
                                                                background-color:#f0edec;
                                                                border-radius:7px;
                                                                text-align:center;
                                                                line-height:40px;
                                                                font-size:18px;
                                                            '>
                                                                🛡️
                                                            </div>
                                                        </td>

                                                        <td valign='middle'>

                                                            <div style='
                                                                font-size:13px;
                                                                line-height:18px;
                                                                font-weight:700;
                                                                color:#1c1b1b;
                                                            '>
                                                                Acceso seguro
                                                            </div>

                                                            <div style='
                                                                margin-top:3px;
                                                                font-size:11px;
                                                                line-height:16px;
                                                                color:#717782;
                                                            '>
                                                                Enlace temporal y protegido
                                                            </div>

                                                        </td>

                                                    </tr>
                                                </table>

                                            </td>


                                            <td
                                                width='50%'
                                                valign='top'
                                                style='padding-left:10px;'
                                            >

                                                <table
                                                    cellpadding='0'
                                                    cellspacing='0'
                                                    border='0'
                                                >
                                                    <tr>

                                                        <td
                                                            width='42'
                                                            valign='top'
                                                            style='
                                                                padding-right:12px;
                                                            '
                                                        >
                                                            <div style='
                                                                width:40px;
                                                                height:40px;
                                                                background-color:#f0edec;
                                                                border-radius:7px;
                                                                text-align:center;
                                                                line-height:40px;
                                                                font-size:18px;
                                                            '>
                                                                ⚡
                                                            </div>
                                                        </td>

                                                        <td valign='middle'>

                                                            <div style='
                                                                font-size:13px;
                                                                line-height:18px;
                                                                font-weight:700;
                                                                color:#1c1b1b;
                                                            '>
                                                                Acceso rápido
                                                            </div>

                                                            <div style='
                                                                margin-top:3px;
                                                                font-size:11px;
                                                                line-height:16px;
                                                                color:#717782;
                                                            '>
                                                                Sin necesidad de contraseña
                                                            </div>

                                                        </td>

                                                    </tr>
                                                </table>

                                            </td>

                                        </tr>
                                    </table>

                                </td>
                            </tr>


                            <!-- FOOTER -->
                            <tr>
                                <td
                                    style='
                                        background-color:#f6f3f2;
                                        padding:28px 40px;
                                        text-align:center;
                                        border-top:1px solid #e5e2e1;
                                    '
                                >

                                    <div style='
                                        font-size:15px;
                                        line-height:22px;
                                        font-weight:700;
                                        color:#1c1b1b;
                                    '>
                                        Gacela Conecta
                                    </div>

                                    <div style='
                                        margin-top:5px;
                                        font-size:11px;
                                        line-height:17px;
                                        color:#717782;
                                    '>
                                        Plataforma corporativa del Grupo Gacela
                                    </div>

                                    <div style='
                                        margin-top:16px;
                                        font-size:10px;
                                        line-height:16px;
                                        color:#9ca3af;
                                    '>
                                        Este correo fue generado automáticamente.
                                        <br>
                                        Si no solicitaste este acceso, puedes ignorar
                                        este mensaje.
                                    </div>

                                </td>
                            </tr>

                        </table>

                    </td>
                </tr>
            </table>

            </body>
            </html>
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mail error: " . $mail->ErrorInfo);
            return false;
        }
    }
}