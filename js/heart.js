(function() {

    $('#heart').fadeIn( 600 );

    const particleOptions = {
            style: 'fill',
            type: 'circle',
            color: '#f90068',
            direction: 'bottom',
            duration: 500,
            easing: 'easeOutSine',
            speed: .25,
            oscillationCoefficient: 120,
            particlesAmountCoefficient: 20,
            complete(){
                location.reload();
            }
    };

    const bttn = document.querySelector('button');
    var curFont = parseInt($('.fa-heart').css('font-size'));
    let buttonVisible = false;

    var particles = new Particles('button', particleOptions);

    $('button').click(function(){

        if ( !particles.isAnimating() && !buttonVisible ) {
            particles.disintegrate();
            buttonVisible = !buttonVisible;
            curFont += 3;
            // 400 max
            $('.fa-heart').css('font-size', curFont + 'px');
            $(this).blur();

            $.ajax({
                type: "POST",
                url: "index.php",
                data: {fontsize: curFont},
                success: function (msg) {
                }
            });
        }
    });
})();
