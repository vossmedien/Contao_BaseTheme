<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>iFrame-Warnung</title>
    <style>
        html {
            height: 100%;
            width: 100%;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0px;
            padding: 0px;
            min-height: 100%;
            min-width: 100%;
            background-color: #eee;
            border: 5px solid red;
            display: flex;
            flex-flow: row wrap;
            justify-content: space-around;
            align-content: center;
            align-items: center;
        }

        * {
            box-sizing: border-box
        }
    </style>
    <script type="text/javascript" async src="/files/base/layout/_vendor/node_modules/js-cookie/dist/js.cookie.min.js"></script>


</head>
<body>


<div style="height: 100%; min-width: 100%; padding: 15px; text-align: center; ">
    <div style="">
        {{iflng::de}}
        <strong style="display: block; font-weight: bold; font-size: 18px; margin-bottom: 1rem;">Um diesen Inhalt sehen
            zu können, müssen Cookies akzeptiert werden.</strong>
        Sie können Ihre Einstellungen jederzeit über diesen Link <a onclick="reset()" href="" class="reset-cookies"
                                                                    style="margin: 15px 0; display: block;">Cookie-Einstellungen
            zurücksetzen</a> zurücksetzen.
        {{iflng}}

        {{iflng::en}}
        <strong style="display: block; font-weight: bold; font-size: 18px; margin-bottom: 1rem;">To view this content,
            cookies must be accepted.</strong>
        You can reset your preferences at any time via this link <a onclick="reset()" href="" class="reset-cookies"
                                                                    style="margin: 15px 0; display: block;">Reset cookie
            preferences</a>.
        {{iflng}}
    </div>
</div>
</body>

<script type="text/javascript" async>

    function reset() {


        if (confirm('Dadurch werden alle Cookies gelöscht und die Seite wird neu geladen, fortfahren?')) {

            window.localStorage.clear();

            var cookies = document.cookie.split("; ");
            for (var c = 0; c < cookies.length; c++) {
                var d = window.location.hostname.split(".");
                while (d.length > 0) {
                    var cookieBase = encodeURIComponent(cookies[c].split(";")[0].split("=")[0]) + '=; expires=Thu, 01-Jan-1970 00:00:01 GMT; domain=' + d.join('.') + ' ;path=';
                    var p = location.pathname.split('/');
                    document.cookie = cookieBase + '/';
                    while (p.length > 0) {
                        document.cookie = cookieBase + p.join('/');
                        p.pop();
                    }
                    d.shift();
                }
            }

            Object.keys(Cookies.get()).forEach(function (cookieName) {
                var neededAttributes = {};
                Cookies.remove(cookieName, neededAttributes);
            });

            window.top.location.reload();


        }

    }
</script>
</html>