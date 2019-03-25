/*
	Component: ui.render 
 */
$.extend(UI, {
	render: function(options) {
        options = options || {};
		var seg = (options.segmentToOpen || false);
		this.segmentToScrollAtRender = (seg) ? seg : false;
//		this.isWebkit = $.browser.webkit;
//		this.isChrome = $.browser.webkit && !!window.chrome;
//		this.isFirefox = $.browser.mozilla;
//		this.isSafari = $.browser.webkit && !window.chrome;
		this.isSafari = (navigator.userAgent.search("Safari") >= 0 && navigator.userAgent.search("Chrome") < 0);
		this.isChrome = (typeof window.chrome != 'undefined');
		this.isFirefox = (typeof navigator.mozApps != 'undefined');
//		console.log('body.scrollTop: ', $('body').scrollTop());
//		console.log('window.scrollTop: ', $(window).scrollTop());
		this.isMac = (navigator.platform == 'MacIntel') ? true : false;

		this.initSegNum = 100; // number of segments initially loaded
		this.moreSegNum = 25;
		this.numOpenedSegments = 0;

		this.loadingMore = false;
		this.infiniteScroll = true;
		this.noMoreSegmentsAfter = false;
		this.noMoreSegmentsBefore = false;
		this.blockButtons = false;
		this.blockOpenSegment = false;
		this.dmp = new diff_match_patch();
		this.beforeDropEditareaHTML = '';
		this.beforeDropSearchSourceHTML = '';
		this.currentConcordanceField = null;
		this.droppingInEditarea = false;
		this.draggingInsideEditarea = false;
		this.undoStack = [];
		this.undoStackPosition = 0;
		this.ccSourceUndoStack = [];
		this.ccSourceUndoStackPosition = 0;
		this.ccTargetUndoStack = [];
		this.ccTargetUndoStackPosition = 0;
		this.tagSelection = false;
		this.nextUntranslatedSegmentIdByServer = 0;
		this.cursorPlaceholder = '[[placeholder]]';
		this.openTagPlaceholder = 'MATECAT-openTagPlaceholder-MATECAT';
		this.closeTagPlaceholder = 'MATECAT-closeTagPlaceholder-MATECAT';
		this.tempViewPoint = '';
		this.checkUpdatesEvery = 180000;
		this.goingToNext = false;
		this.preCloseTagAutocomplete = false;
        this.hiddenTextEnabled = true;
        this.markSpacesEnabled = false;
		this.setGlobalTagProjection();
        this.tagModesEnabled = (typeof options.tagModesEnabled != 'undefined')? options.tagModesEnabled : true;
        if(this.tagModesEnabled && !this.enableTagProjection) {
            UI.body.addClass('tagModes');
        } else {
            UI.body.removeClass('tagModes');
        }

        /**
         * Global Translation mismatches array definition.
         */
        this.translationMismatches = [];

        this.downOpts = {offset: '130%'};
		this.upOpts = {offset: '-40%'};
		this.readonly = (this.body.hasClass('archived')) ? true : false;
		this.suggestionShortcutLabel = 'CTRL+';

		this.taglockEnabled = config.taglockEnabled;
		this.debug = false;
        this.findCommonPartInSegmentIds();
		UI.detectStartSegment();

		options.openCurrentSegmentAfter = ( (!seg) && (!UI.firstLoad) ) ? true : false ;

		if ( UI.firstLoad ) {

			this.lastUpdateRequested = new Date();

			setTimeout(function() {
				UI.getUpdates();
			}, UI.checkUpdatesEvery);

		}

		return UI.getSegments(options).done(function() {
			// Force the firstLoad flag to false whenever the segments are fetched
			UI.firstLoad = false ;
		});

	},
});

