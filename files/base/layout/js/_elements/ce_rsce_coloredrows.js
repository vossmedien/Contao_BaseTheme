/* Wrap Headlines from colored-rows in extra span */
if ($(".ce--coloredrows").length) {
    $(
        ".ce--coloredrows h1, .ce--coloredrows .h1, .ce--coloredrows h2, .ce--coloredrows .h2"
    ).each(function (i, v) {
        $(this).wrapInner("<span><span></span></span>");
    });
}
/* END */