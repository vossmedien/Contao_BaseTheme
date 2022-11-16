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
    <script src="/files/base/layout/_vendor/node_modules/js-cookie/dist/js.cookie.min.js"></script>


</head>
<body>


<div style="height: 100%; min-width: 100%; padding: 15px; text-align: center; ">
    <div style="">
        <strong style="display: block; font-weight: bold; font-size: 18px; margin-bottom: 1rem;">Um diesen Inhalt sehen
            zu können, müssen
            Cookies akzeptiert werden.</strong>
        Sie können Ihre Einstellungen jederzeit über diesen Link <a onclick="reset()" href="" class="reset-cookies"
                                                                    style="margin: 15px 0; display: block;">Cookie-Einstellungen
            zurücksetzen</a>
        zurücksetzen.
    </div>
</div>
</body>

<script>

    function reset() {


        if (confirm('Dadurch werden alle Cookies gelöscht und die Seite wird neu geladen, fortfahren?')) {
            if (confirm('Alle Cookies wurden gelöscht und Einstellungen zurückgesetzt, die Seite wird nun neu geladen.')) {
                window.localStorage.clear();

                Object.keys(Cookies.get()).forEach(function (cookieName) {
                    var neededAttributes = {};
                    Cookies.remove(cookieName, neededAttributes);
                });


                window.top.location.reload();


            }
        } else {

        }

    }
</script>
</html>