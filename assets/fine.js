$(function () {
    $(document).keydown(function (event) {
        var $tmp;

        switch (event.which) {
            case 37: // Left Arrow
                $tmp = $('[data-action~="navigation-previous"]');
                if ($tmp.length > 0) {
                    window.location = $tmp.attr('href');
                }
                break;
            case 39: // Right Arrow
                $tmp = $('[data-action~="navigation-next"]');
                if ($tmp.length > 0) {
                    window.location = $tmp.attr('href');
                }
                break;
            case 68: // D
                $tmp = $('[data-action~="navigation-detail"]');
                if ($tmp.length > 0) {
                    window.location = $tmp.attr('href');
                }
                break;
            case 82: // R
                $tmp = $('[data-action~="navigation-random"]');
                if ($tmp.length > 0) {
                    window.location = $tmp.attr('href');
                }
                break;
        }
    });

    $(document).keyup(function (e) {
        if (e.keyCode === 27) {
            var $tmp = $('[data-action~="navigation-up"]');
            if ($tmp.length > 0) {
                window.location = $tmp.attr('href');
            }
        }
    });

    $(document).on('swipeleft', function () {
        var e = $.Event('keydown', { which: 39 /* Right Arrow */ });
        $(document).trigger(e);
    });

    $(document).on('swiperight', function () {
        var e = $.Event('keydown', { which: 37 /* Left Arrow */ });
        $(document).trigger(e);
    });
});
