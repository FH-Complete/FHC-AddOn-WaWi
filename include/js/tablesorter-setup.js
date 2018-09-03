$(document).ready(function(){

    $.tablesorter.addParser({
            id: "dedate",
            is: function(s) {
                return /^\d{1,2}[.]\d{1,2}[.]\d{2,4}$/.test(s);
            },
            format: function(s) {
                s = s.replace(/(\d{1,2}).(\d{1,2}).(\d{2,4})/, "$3/$2/$1");
                return $.tablesorter.formatFloat(new Date(s).getTime());
            },
          type: "numeric"
    });

	$.tablesorter.addParser({
            id: "dedateMitText",
            is: function(s) {
                return /^\d{1,2}[.]\d{1,2}[.]\d{2,4} \w*$/.test(s);
            },
            format: function(s) {
                s = s.replace(/(\d{1,2}).(\d{1,2}).(\d{2,4}) \w*/, "$3/$2/$1");
                return $.tablesorter.formatFloat(new Date(s).getTime());
            },
          type: "numeric"
    });

    $.tablesorter.addParser({
        id: "digitmittausenderpunkt",
        is: function(s) {
            return /^[0-9]*[.]*[0-9]*[,]*[0-9]*$/.test(s);
        },
        format: function(s) {
            return $.tablesorter.formatFloat(s.replace('.',""));
        },
        type: "numeric"
    });

    $.tablesorter.addParser({
        id: "DatummitUhrzeit",
        is: function(s) {
            return s.match(new RegExp(/^[0-9]{1,2}.[0-9]{1,2}.[0-9]{4} (([0-2]?[0-9]:[0-5][0-9])|([0-1]?[0-9]:[0-5][0-9]))$/));
        },
        format: function(s) {
            return $.tablesorter.formatFloat(new Date(s.replace(/(\d{1,2})[\/\.](\d{1,2})[\/\.](\d{4})/, "$3/$2/$1")).getTime());
        },
        type: "numeric"
    });

    $.tablesorter.defaults.dateFormat = "dedate";
    $.tablesorter.defaults.decimal = ",";

});
