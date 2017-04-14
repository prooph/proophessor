function loadFunFacts(funFactsResource) {
    //Animated Number
    $.fn.animateNumbers = function(stop, commas, duration, ease) {
        return this.each(function() {
            var $this = $(this);
            var start = parseInt($this.text().replace(/,/g, ""));
            commas = (commas === undefined) ? true : commas;
            $({value: start}).animate({value: stop}, {
                duration: duration == undefined ? 1000 : duration,
                easing: ease == undefined ? "swing" : ease,
                step: function() {
                    $this.text(Math.floor(this.value));
                    if (commas) { $this.text($this.text().replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,")); }
                },
                complete: function() {
                    if (parseInt($this.text()) !== stop) {
                        $this.text(stop);
                        if (commas) { $this.text($this.text().replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,")); }
                    }
                }
            });
        });
    };

    $.get(funFactsResource).then(function (response) {
        $('#animated-number').removeClass('hidden');

        $('#animated-number-downloads').data('digit', response.fun_facts.downloads);
        $('#animated-number-stars').data('digit', response.fun_facts.github_stars);
        $('#animated-number-people').data('digit', response.fun_facts.gitter_people.length);

        $('.animated-number').bind('inview', function(event, visible, visiblePartX, visiblePartY) {
            var $this = $(this);
            if (visible) {
                $this.animateNumbers($this.data('digit'), false, $this.data('duration'));
                $this.unbind('inview');
            }
        });

        $('#community').removeClass('hidden');

        var $gitterWall = $('#gitter-wall');
        var $gitterWallTpl = $gitterWall.find('#gitter-wall-tpl');
        var gitterPeople = response.fun_facts.gitter_people;

        for(var i in gitterPeople) {
        	$pic = $gitterWallTpl.clone();
        	$pic.removeClass('hidden');
        	$pic.find('img').attr({
        		src: gitterPeople[i].avatarUrlSmall,
				alt: gitterPeople[i].username,
				title: gitterPeople[i].username
        	});
        	$gitterWall.append($pic);
		}

        $gitterWall.owlCarousel({
            loop: true,
            autoPlay: 2000,
            items: 12, //12 items above
            itemsDesktop: [1199, 10], //8 items between 1199 and 992px
            itemsDesktopSmall: [991, 8], //6 betweem 900px and 601px
            itemsTablet: [767, 6], //4 items between 600 and 480
            itemsMobile : [575,4] // 2 item between 479 and 0
        });

		var renderCoverage = function (package, coverageData) {
        	var coverage = coverageData[package] || 0;
			$('#coverage-'+package).data('width', coverage).html(coverage+"%");
        }

        renderCoverage('event-store', response.fun_facts.coverage);
        renderCoverage('pdo-event-store', response.fun_facts.coverage);
        renderCoverage('event-sourcing', response.fun_facts.coverage);
        renderCoverage('service-bus', response.fun_facts.coverage);
    })
}

jQuery(function($) {'use strict';

    //MENU APPEAR AND HIDE
    $(document).ready(function() {

        "use strict";

        $(window).scroll(function() {

            "use strict";

            if ($(window).scrollTop() > 80) {
                $(".navbar").css({
                    'margin-top': '0px',
                    'opacity': '1'
                })
                $(".navbar-nav>li>a").css({
                    'padding-top': '15px'
                });
                $(".navbar-brand img").css({
                    'height': '35px'
                });
                $(".navbar-brand img").css({
                    'padding-top': '0px'
                });
                $(".navbar-default").css({
                    'background-color': 'rgba(59, 59, 59, 0.7)'
                });
            } else {
                $(".navbar").css({
                    'margin-top': '-100px',
                    'opacity': '0'
                })
                $(".navbar-nav>li>a").css({
                    'padding-top': '45px'
                });
                $(".navbar-brand img").css({
                    'height': '45px'
                });
                $(".navbar-brand img").css({
                    'padding-top': '20px'
                });
                $(".navbar-default").css({
                    'background-color': 'rgba(59, 59, 59, 0)'
                });
            }
        });
    });

	// Navigation Scroll
	$(window).scroll(function(event) {
		Scroll();
	});

	$('.navbar-collapse ul li a, a.scroll').on('click', function() {
		$('html, body').animate({scrollTop: $(this.hash).offset().top - 5}, 1000);
		return false;
	});

	// User define function
	function Scroll() {
		var contentTop      =   [];
		var contentBottom   =   [];
		var winTop      =   $(window).scrollTop();
		var rangeTop    =   200;
		var rangeBottom =   500;
		$('.navbar-collapse').find('.scroll a').each(function(){
			contentTop.push( $( $(this).attr('href') ).offset().top);
			contentBottom.push( $( $(this).attr('href') ).offset().top + $( $(this).attr('href') ).height() );
		})
		$.each( contentTop, function(i){
			if ( winTop > contentTop[i] - rangeTop ){
				$('.navbar-collapse li.scroll')
				.removeClass('active')
				.eq(i).addClass('active');			
			}
		})
	};

	$('#tohash').on('click', function(){
		$('html, body').animate({scrollTop: $(this.hash).offset().top - 5}, 1000);
		return false;
	});

	// accordian
	$('.accordion-toggle').on('click', function(){
		$(this).closest('.panel-group').children().each(function(){
		$(this).find('>.panel-heading').removeClass('active');
		 });

	 	$(this).closest('.panel-heading').toggleClass('active');
	});

    //PARALLAX
    $(document).ready(function() {

        "use strict";

        $(window).bind('load', function() {
            "use strict";
            parallaxInit();
        });

        function parallaxInit() {
            "use strict";
            $('.home-parallax').parallax("30%", 0.1);
			/*add as necessary*/
        }
    });

    // FIX HOME SCREEN HEIGHT
    setInterval(function() {

        "use strict";

        var widnowHeight = $(window).height();
        var containerHeight = $(".home-container").height();
        var padTop = widnowHeight - containerHeight;
        $(".home-container").css({
            'padding-top': Math.round(padTop / 2) + 'px',
            'padding-bottom': Math.round(padTop / 2) + 'px'
        });
    }, 10)

	//Initiat WOW JS
	new WOW().init();
	//smoothScroll
	smoothScroll.init();

	// portfolio filter
	$(window).load(function(){'use strict';
		var $portfolio_selectors = $('.portfolio-filter >li>a');
		var $portfolio = $('.portfolio-items');
		$portfolio.isotope({
			itemSelector : '.portfolio-item',
			layoutMode : 'fitRows'
		});
		
		$portfolio_selectors.on('click', function(){
			$portfolio_selectors.removeClass('active');
			$(this).addClass('active');
			var selector = $(this).attr('data-filter');
			$portfolio.isotope({ filter: selector });
			return false;
		});
	});

	$(document).ready(function() {
		//Animated Progress
		$('.progress-bar').bind('inview', function(event, visible, visiblePartX, visiblePartY) {
			if (visible) {
				$(this).css('width', $(this).data('width') + '%');
				$(this).unbind('inview');
			}
		});
	});

	// Contact form
	var form = $('#main-contact-form');
	form.submit(function(event){
		event.preventDefault();
		var form_status = $('<div class="form_status"></div>');
		$.ajax({
			url: $(this).attr('action'),
			beforeSend: function(){
				form.prepend( form_status.html('<p><i class="fa fa-spinner fa-spin"></i> Email is sending...</p>').fadeIn() );
			}
		}).done(function(data){
			form_status.html('<p class="text-success">Thank you for contact us. As early as possible  we will contact you</p>').delay(3000).fadeOut();
		});
	});

});