/**
 * Smart FAQ Schema — Frontend JavaScript
 * Pure vanilla JS. No jQuery. Elementor-safe.
 * Loaded from footer — never blocks page render.
 */
(function() {
	'use strict';

	/**
	 * Initialize all un-initialized FAQ sections on the page.
	 * Exposed as window.sfaqInitAll so auto-inject JS can call it after DOM insertion.
	 */
	function initAll() {
		var sections = document.querySelectorAll( '.sfaq-section:not([data-sfaq-init])' );
		for ( var i = 0; i < sections.length; i++ ) {
			initSection( sections[i] );
		}
	}

	/**
	 * Wire up a single .sfaq-section element.
	 */
	function initSection( section ) {
		if ( section.getAttribute( 'data-sfaq-init' ) ) return;
		section.setAttribute( 'data-sfaq-init', '1' );

		var buttons = section.querySelectorAll( '.sfaq-question' );
		for ( var i = 0; i < buttons.length; i++ ) {
			( function( btn ) {
				btn.addEventListener( 'click', function( e ) {
					e.preventDefault();
					toggleItem( btn, section );
				} );

				// Arrow key navigation (ARIA accordion pattern)
				btn.addEventListener( 'keydown', function( e ) {
					if ( 'ArrowDown' === e.key ) { e.preventDefault(); focusNext( btn, section ); }
					else if ( 'ArrowUp'   === e.key ) { e.preventDefault(); focusPrev( btn, section ); }
					else if ( 'Home'      === e.key ) { e.preventDefault(); focusFirst( section ); }
					else if ( 'End'       === e.key ) { e.preventDefault(); focusLast( section ); }
				} );
			} )( buttons[i] );
		}
	}

	/**
	 * Toggle a single FAQ item open or closed.
	 * When opening, closes all other items in the same section first.
	 */
	function toggleItem( btn, section ) {
		var isExpanded = 'true' === btn.getAttribute( 'aria-expanded' );

		if ( isExpanded ) {
			closeItem( btn );
		} else {
			// Close all other open items in this section first
			var openBtns = section.querySelectorAll( '.sfaq-question[aria-expanded="true"]' );
			for ( var i = 0; i < openBtns.length; i++ ) {
				if ( openBtns[i] !== btn ) {
					closeItem( openBtns[i] );
				}
			}
			openItem( btn );
		}
	}

	function openItem( btn ) {
		var panelId = btn.getAttribute( 'aria-controls' );
		var panel   = panelId ? document.getElementById( panelId ) : null;
		if ( ! panel ) return;

		btn.setAttribute( 'aria-expanded', 'true' );
		panel.removeAttribute( 'hidden' );

		var item = btn.closest( '.sfaq-item' );
		if ( item ) item.classList.add( 'sfaq-open' );

		setIcon( btn, true );
	}

	function closeItem( btn ) {
		var panelId = btn.getAttribute( 'aria-controls' );
		var panel   = panelId ? document.getElementById( panelId ) : null;
		if ( ! panel ) return;

		btn.setAttribute( 'aria-expanded', 'false' );
		panel.setAttribute( 'hidden', '' );

		var item = btn.closest( '.sfaq-item' );
		if ( item ) item.classList.remove( 'sfaq-open' );

		setIcon( btn, false );
	}

	// Icon visibility is handled purely by CSS via aria-expanded selector
	function setIcon() {}

	// Arrow key helpers
	function focusNext( current, section ) {
		var btns = section.querySelectorAll( '.sfaq-question' );
		for ( var i = 0; i < btns.length - 1; i++ ) {
			if ( btns[i] === current ) { btns[i + 1].focus(); return; }
		}
	}
	function focusPrev( current, section ) {
		var btns = section.querySelectorAll( '.sfaq-question' );
		for ( var i = 1; i < btns.length; i++ ) {
			if ( btns[i] === current ) { btns[i - 1].focus(); return; }
		}
	}
	function focusFirst( section ) {
		var first = section.querySelector( '.sfaq-question' );
		if ( first ) first.focus();
	}
	function focusLast( section ) {
		var btns = section.querySelectorAll( '.sfaq-question' );
		if ( btns.length ) btns[btns.length - 1].focus();
	}

	/**
	 * Boot — run when DOM is ready.
	 */
	function boot() {
		initAll();

		// MutationObserver for dynamically injected FAQ sections (Elementor widgets, auto-inject)
		if ( typeof MutationObserver !== 'undefined' ) {
			var observer = new MutationObserver( function( mutations ) {
				for ( var i = 0; i < mutations.length; i++ ) {
					var nodes = mutations[i].addedNodes;
					for ( var j = 0; j < nodes.length; j++ ) {
						if ( nodes[j].nodeType !== 1 ) continue;
						if ( nodes[j].classList && nodes[j].classList.contains( 'sfaq-section' ) ) {
							initSection( nodes[j] );
						} else if ( nodes[j].querySelectorAll ) {
							var found = nodes[j].querySelectorAll( '.sfaq-section:not([data-sfaq-init])' );
							for ( var k = 0; k < found.length; k++ ) {
								initSection( found[k] );
							}
						}
					}
				}
			} );
			observer.observe( document.body, { childList: true, subtree: true } );
		}

		// Elementor frontend hook (editor / preview mode)
		if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/shortcode.default', function() {
				initAll();
			} );
		}
		window.addEventListener( 'elementor/frontend/init', function() {
			initAll();
		} );
	}

	// Expose globally so auto-inject inline script can call it after DOM insertion
	window.sfaqInitAll = initAll;

	// Boot
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

} )();
