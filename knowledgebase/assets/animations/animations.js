/**
 * Animation Toolkit — scroll reveal
 * Hardened from the MPD framework (the MPD framework folder).
 *
 * Fixes over the source version:
 *   - re-scans after DOM changes, so AJAX-injected content (query-loop
 *     pagination, filters, load-more) animates instead of staying at
 *     opacity 0 forever
 *   - `data-anim-threshold="0"` is now honoured; `parseFloat(x) || default`
 *     treated 0 as falsy and silently forced it back to 0.1, which made the
 *     documented fix for tall wrappers impossible to express
 *   - stagger children are no longer observed twice, so a child can't reveal
 *     itself out of sequence when it enters the viewport before its container
 *   - exposes window.mpdAnim.refresh() for anything that swaps DOM without
 *     a detectable mutation
 *
 * The `js-anim` gate that makes the hidden state fail-safe is set inline in
 * <head> (see functions.php), not here — this file is deferred and would
 * arrive after first paint.
 */
(function () {
	'use strict';

	var DEFAULTS = {
		threshold: 0.1,
		rootMargin: '0px 0px -40px 0px',
		once: true,
		observeDom: true,
	};

	var ANIM_SELECTORS = [
		'.anim-fade-up',
		'.anim-fade-in',
		'.anim-fade-down',
		'.anim-slide-left',
		'.anim-slide-right',
		'.anim-scale-in',
		'.anim-scale-up',
		'.anim-blur-in',
	].join(',');

	var STAGGER_SELECTORS = [
		'.anim-stagger',
		'.anim-stagger-tight',
		'.anim-stagger-wide',
	].join(',');

	var observed = new WeakSet();
	var elementObserver;
	var staggerObserver;
	var config;
	var reduced = false;

	function reveal( el ) {
		el.classList.add( 'anim-visible' );
	}

	/**
	 * Read a numeric body attribute, accepting 0.
	 * The source used `parseFloat(attr) || fallback`, which discards 0.
	 */
	function numAttr( body, name, fallback ) {
		var raw = body.getAttribute( name );
		if ( raw === null || raw === '' ) { return fallback; }
		var n = parseFloat( raw );
		return isNaN( n ) ? fallback : n;
	}

	/** Observe anything not already being watched. Safe to call repeatedly. */
	function scan() {
		if ( reduced ) {
			document.querySelectorAll( ANIM_SELECTORS ).forEach( function ( el ) {
				if ( ! observed.has( el ) ) { observed.add( el ); reveal( el ); }
			} );
			return;
		}

		document.querySelectorAll( STAGGER_SELECTORS ).forEach( function ( el ) {
			if ( observed.has( el ) ) { return; }
			observed.add( el );
			staggerObserver.observe( el );
		} );

		document.querySelectorAll( ANIM_SELECTORS ).forEach( function ( el ) {
			if ( observed.has( el ) ) { return; }
			// A stagger container orchestrates its own children. Observing them
			// individually as well lets one reveal early and break the sequence.
			if ( el.closest( STAGGER_SELECTORS ) ) { return; }
			observed.add( el );
			elementObserver.observe( el );
		} );
	}

	function init() {
		var body = document.body;

		reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		config = {
			threshold: numAttr( body, 'data-anim-threshold', DEFAULTS.threshold ),
			rootMargin: body.getAttribute( 'data-anim-margin' )
				? '0px 0px ' + body.getAttribute( 'data-anim-margin' ) + ' 0px'
				: DEFAULTS.rootMargin,
			once: body.getAttribute( 'data-anim-once' ) !== 'false',
			observeDom: body.getAttribute( 'data-anim-observe-dom' ) !== 'false',
		};

		if ( ! reduced ) {
			var opts = { threshold: config.threshold, rootMargin: config.rootMargin };

			elementObserver = new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						reveal( entry.target );
						if ( config.once ) { elementObserver.unobserve( entry.target ); }
					} else if ( ! config.once ) {
						entry.target.classList.remove( 'anim-visible' );
					}
				} );
			}, opts );

			staggerObserver = new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					var children = entry.target.querySelectorAll( ANIM_SELECTORS );
					if ( entry.isIntersecting ) {
						children.forEach( reveal );
						if ( config.once ) { staggerObserver.unobserve( entry.target ); }
					} else if ( ! config.once ) {
						children.forEach( function ( c ) { c.classList.remove( 'anim-visible' ); } );
					}
				} );
			}, opts );
		}

		scan();

		// AJAX-injected content. The source observed once at DOMContentLoaded,
		// so anything added later was never watched and stayed hidden for good.
		if ( config.observeDom && 'MutationObserver' in window ) {
			var pending = null;
			new MutationObserver( function ( mutations ) {
				var relevant = mutations.some( function ( m ) {
					return Array.prototype.some.call( m.addedNodes, function ( n ) {
						return n.nodeType === 1 &&
							( n.matches( ANIM_SELECTORS ) ||
							  n.matches( STAGGER_SELECTORS ) ||
							  n.querySelector( ANIM_SELECTORS ) );
					} );
				} );
				if ( ! relevant ) { return; }
				clearTimeout( pending );
				pending = setTimeout( scan, 150 );
			} ).observe( document.body, { childList: true, subtree: true } );
		}

		window.mpdAnim = { refresh: scan };
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
