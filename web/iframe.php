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
    }

    * {
        box-sizing: border-box
    }
</style>


<div
        style="height: 100%; min-width: 100%; padding: 15px; text-align: center; display: flex; flex-flow: row wrap; justify-content: space-around; align-content: center; align-items: center;">
    <div style="">
        <strong style="display: block; font-weight-bold; font-size: 18px; margin-bottom: 1rem;">Um diesen Inhalt sehen
            zu können, müssen
            Cookies akzeptiert werden.</strong>
        Sie können Ihre Einstellungen jederzeit über diesen Link <a onclick="reset()" href="" class="reset-cookies"
                                                                    style="margin: 15px 0; display: block;">Cookie-Einstellungen
            zurücksetzen</a>
        zurücksetzen.
    </div>
</div>


<script>

    function reset() {
        window.localStorage.clear();
        document.cookie.split(";").forEach(function (c) {
            document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
        });

        if (confirm('Alle Cookies wurden gelöscht und Einstellungen zurückgesetzt, die Seite wird nun neu geladen.')) {
            parent.window.location.reload();
        }
    }
</script>