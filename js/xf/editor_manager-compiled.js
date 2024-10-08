/*
 * XenForo dragula.min.js
 * Copyright 2010-2024 XenForo Ltd.
 * Released under the XenForo License Agreement: https://xenforo.com/license-agreement
 */
(function(aa){"object"===typeof exports&&"undefined"!==typeof module?module.exports=aa():"function"===typeof define&&define.amd?define([],aa):("undefined"!==typeof window?window:"undefined"!==typeof global?global:"undefined"!==typeof self?self:this).dragula=aa()})(function(){return function m(w,z,J){function r(g,C){if(!z[g]){if(!w[g]){var x="function"==typeof require&&require;if(!C&&x)return x(g,!0);if(d)return d(g,!0);C=Error("Cannot find module '"+g+"'");throw C.code="MODULE_NOT_FOUND",C;}C=z[g]=
{exports:{}};w[g][0].call(C.exports,function(l){var A=w[g][1][l];return r(A?A:l)},C,C.exports,m,w,z,J)}return z[g].exports}for(var d="function"==typeof require&&require,h=0;h<J.length;h++)r(J[h]);return r}({1:[function(w,z,J){function m(d){var h=r[d];h?h.lastIndex=0:r[d]=h=new RegExp("(?:^|\\s)"+d+"(?:\\s|$)","g");return h}var r={};z.exports={add:function(d,h){var g=d.className;g.length?m(h).test(g)||(d.className+=" "+h):d.className=h},rm:function(d,h){d.className=d.className.replace(m(h)," ").trim()}}},
{}],2:[function(w,z,J){(function(m){function r(c,n,K,N){var S={mouseup:"touchend",mousedown:"touchstart",mousemove:"touchmove"},V={mouseup:"pointerup",mousedown:"pointerdown",mousemove:"pointermove"},Y={mouseup:"MSPointerUp",mousedown:"MSPointerDown",mousemove:"MSPointerMove"};if(m.navigator.pointerEnabled)p[n](c,V[K],N);else if(m.navigator.msPointerEnabled)p[n](c,Y[K],N);else p[n](c,S[K],N),p[n](c,K,N)}function d(c){if(void 0!==c.touches)return c.touches.length;if(void 0!==c.which&&0!==c.which)return c.which;
if(void 0!==c.buttons)return c.buttons;c=c.button;if(void 0!==c)return c&1?1:c&2?3:c&4?2:0}function h(c,n){return"undefined"!==typeof m[n]?m[n]:L.clientHeight?L[c]:q.body[c]}function g(c,n,K){c=c||{};var N=c.className;c.className+=" gu-hide";n=q.elementFromPoint(n,K);c.className=N;return n}function C(){return!1}function x(){return!0}function l(c){return c.parentNode===q?null:c.parentNode}function A(c){return"INPUT"===c.tagName||"TEXTAREA"===c.tagName||"SELECT"===c.tagName||O(c)}function O(c){return c&&
"false"!==c.contentEditable?"true"===c.contentEditable?!0:O(l(c)):!1}function G(c){var n;if(!(n=c.nextElementSibling)){do c=c.nextSibling;while(c&&1!==c.nodeType);n=c}return n}function k(c,n){n=n.targetTouches&&n.targetTouches.length?n.targetTouches[0]:n.changedTouches&&n.changedTouches.length?n.changedTouches[0]:n;var K={pageX:"clientX",pageY:"clientY"};c in K&&!(c in n)&&K[c]in n&&(c=K[c]);return n[c]}var y=w("contra/emitter"),p=w("crossvent"),t=w("./classes"),q=document,L=q.documentElement;z.exports=
function(c,n){function K(a){return-1!==v.containers.indexOf(a)||e.isContainer(a)}function N(a){a=a?"remove":"add";r(L,a,"mousedown",ua);r(L,a,"mouseup",ba)}function S(a){r(L,a?"remove":"add","mousemove",va)}function V(a){a=a?"remove":"add";p[a](L,"selectstart",Y);p[a](L,"click",Y)}function Y(a){T&&a.preventDefault()}function ua(a){ia=a.clientX;ja=a.clientY;if(1===d(a)&&!a.metaKey&&!a.ctrlKey){var b=a.target,f=ca(b);f&&(T=f,S(),"mousedown"===a.type&&(A(b)?b.focus():a.preventDefault()))}}function va(a){if(T)if(0===
d(a))ba({});else if(void 0===a.clientX||a.clientX!==ia||void 0===a.clientY||a.clientY!==ja){if(e.ignoreInputTextSelection){var b=k("clientX",a);var f=k("clientY",a);b=q.elementFromPoint(b,f);if(A(b))return}b=T;S(!0);V();ka();la(b);f=H.getBoundingClientRect();b=f.left+h("scrollLeft","pageXOffset");f=f.top+h("scrollTop","pageYOffset");ma=k("pageX",a)-b;na=k("pageY",a)-f;t.add(D||H,"gu-transit");E||(b=H.getBoundingClientRect(),E=H.cloneNode(!0),E.style.width=(b.width||b.right-b.left)+"px",E.style.height=
(b.height||b.bottom-b.top)+"px",t.rm(E,"gu-transit"),t.add(E,"gu-mirror"),e.mirrorContainer.appendChild(E),r(L,"add","mousemove",da),t.add(e.mirrorContainer,"gu-unselectable"),v.emit("cloned",E,H,"mirror"));da(a)}}function ca(a){if(!(v.dragging&&E||K(a))){for(var b=a;l(a)&&!1===K(l(a));){if(e.invalid(a,b))return;a=l(a);if(!a)return}var f=l(a);if(f&&!e.invalid(a,b)&&e.moves(a,f,b,G(a)))return{item:a,source:f}}}function la(a){if("boolean"===typeof e.copy?e.copy:e.copy(a.item,a.source))D=a.item.cloneNode(!0),
v.emit("cloned",D,a.item,"copy");B=a.source;H=a.item;W=U=G(a.item);v.dragging=!0;v.emit("drag",H,B)}function wa(){return!1}function ka(){if(v.dragging){var a=D||H;oa(a,l(a))}}function ba(a){T=!1;S(!0);V(!0);if(v.dragging){var b=D||H,f=k("clientX",a);a=k("clientY",a);var F=g(E,f,a);(f=pa(F,f,a))&&(D&&e.copySortSource||!D||f!==B)?oa(b,f):e.removeOnSpill?qa():ra()}}function oa(a,b){var f=l(a);D&&e.copySortSource&&b===B&&f.removeChild(H);ea(b)?v.emit("cancel",a,B,B):v.emit("drop",a,b,B,U);fa()}function qa(){if(v.dragging){var a=
D||H,b=l(a);b&&b.removeChild(a);v.emit(D?"cancel":"remove",a,b,B);fa()}}function ra(a){if(v.dragging){var b=0<arguments.length?a:e.revertOnSpill,f=D||H,F=l(f),u=ea(F);!1===u&&b&&(D?F&&F.removeChild(D):B.insertBefore(f,W));u||b?v.emit("cancel",f,B,B):v.emit("drop",f,F,B,U);fa()}}function fa(){var a=D||H;T=!1;S(!0);V(!0);E&&(t.rm(e.mirrorContainer,"gu-unselectable"),r(L,"remove","mousemove",da),l(E).removeChild(E),E=null);a&&t.rm(a,"gu-transit");ha&&clearTimeout(ha);v.dragging=!1;P&&v.emit("out",a,
P,B);v.emit("dragend",a);B=H=D=W=U=ha=P=null}function ea(a,b){b=void 0!==b?b:E?U:G(D||H);return a===B&&b===W}function pa(a,b,f){function F(){if(!1===K(u))return!1;var M=sa(u,a);M=ta(u,M,b,f);return ea(u,M)?!0:e.accepts(H,u,B,M)}for(var u=a;u&&!F();)u=l(u);return u}function da(a){if(E){a.preventDefault();var b=k("clientX",a);a=k("clientY",a);var f=a-na;E.style.left=b-ma+"px";E.style.top=f+"px";f=D||H;var F=g(E,b,a),u=pa(F,b,a),M=null!==u&&u!==P;if(M||null===u)P&&v.emit("out",f,P,B),P=u,M&&v.emit("over",
f,P,B);var Q=l(f);if(u===B&&D&&!e.copySortSource)Q&&Q.removeChild(f);else{F=sa(u,F);if(null!==F)b=ta(u,F,b,a);else{if(!0!==e.revertOnSpill||D){D&&Q&&Q.removeChild(f);return}b=W;u=B}if(null===b&&M||b!==f&&b!==G(f))U=b,u.insertBefore(f,b),v.emit("shadow",f,u,B)}}}function xa(a){t.rm(a,"gu-hide")}function ya(a){v.dragging&&t.add(a,"gu-hide")}function sa(a,b){for(;b!==a&&l(b)!==a;)b=l(b);return b===L?null:b}function ta(a,b,f,F){function u(){var I=a.children.length,R;for(R=0;R<I;R++){var X=a.children[R];
var Z=X.getBoundingClientRect();if(Q&&Z.left+Z.width/2>f||!Q&&Z.top+Z.height/2>F)return X}return null}function M(){var I=b.getBoundingClientRect();if(za){var R=F-I.top,X=f-I.left;I=Math.min(X,I.right-f,R,I.bottom-F);return X===I||R===I?G(b):b}return Q?f>I.left+(I.width||I.right-I.left)/2?G(b):b:F>I.top+(I.height||I.bottom-I.top)/2?G(b):b}var Q="horizontal"===e.direction,za="grid"===e.direction;return b!==a?M():u()}1===arguments.length&&!1===Array.isArray(c)&&(n=c,c=[]);var E,B,H,ma,na,ia,ja,W,U,D,
ha,P=null,T,e=n||{};void 0===e.moves&&(e.moves=x);void 0===e.accepts&&(e.accepts=x);void 0===e.invalid&&(e.invalid=wa);void 0===e.containers&&(e.containers=c||[]);void 0===e.isContainer&&(e.isContainer=C);void 0===e.copy&&(e.copy=!1);void 0===e.copySortSource&&(e.copySortSource=!1);void 0===e.revertOnSpill&&(e.revertOnSpill=!1);void 0===e.removeOnSpill&&(e.removeOnSpill=!1);void 0===e.direction&&(e.direction="vertical");void 0===e.ignoreInputTextSelection&&(e.ignoreInputTextSelection=!0);void 0===
e.mirrorContainer&&(e.mirrorContainer=q.body);var v=y({containers:e.containers,start:function(a){(a=ca(a))&&la(a)},end:ka,cancel:ra,remove:qa,destroy:function(){N(!0);ba({})},canMove:function(a){return!!ca(a)},dragging:!1});if(!0===e.removeOnSpill)v.on("over",xa).on("out",ya);N();return v}}).call(this,"undefined"!==typeof global?global:"undefined"!==typeof self?self:"undefined"!==typeof window?window:{})},{"./classes":1,"contra/emitter":5,crossvent:6}],3:[function(w,z,J){z.exports=function(m,r){return Array.prototype.slice.call(m,
r)}},{}],4:[function(w,z,J){var m=w("ticky");z.exports=function(r,d,h){r&&m(function(){r.apply(h||null,d||[])})}},{ticky:9}],5:[function(w,z,J){var m=w("atoa"),r=w("./debounce");z.exports=function(d,h){var g=h||{},C={};void 0===d&&(d={});d.on=function(x,l){C[x]?C[x].push(l):C[x]=[l];return d};d.once=function(x,l){l._once=!0;d.on(x,l);return d};d.off=function(x,l){var A=arguments.length;if(1===A)delete C[x];else if(0===A)C={};else{A=C[x];if(!A)return d;A.splice(A.indexOf(l),1)}return d};d.emit=function(){var x=
m(arguments);return d.emitterSnapshot(x.shift()).apply(this,x)};d.emitterSnapshot=function(x){var l=(C[x]||[]).slice(0);return function(){var A=m(arguments),O=this||d;if("error"===x&&!1!==g.throws&&!l.length)throw 1===A.length?A[0]:A;l.forEach(function(G){g.async?r(G,A,O):G.apply(O,A);G._once&&d.off(x,G)});return d}};return d}},{"./debounce":4,atoa:3}],6:[function(w,z,J){(function(m){function r(k,y,p){var t=k.attachEvent,q=g(k,y,p)||h(k,y,p);G.push({wrapper:q,element:k,type:y,fn:p});return t.call(k,
"on"+y,q)}function d(k,y,p){if(p=g(k,y,p))return k.detachEvent("on"+y,p)}function h(k,y,p){return function(t){var q=t||m.event;q.target=q.target||q.srcElement;q.preventDefault=q.preventDefault||function(){q.returnValue=!1};q.stopPropagation=q.stopPropagation||function(){q.cancelBubble=!0};q.which=q.which||q.keyCode;p.call(k,q)}}function g(k,y,p){a:{var t;for(t=0;t<G.length;t++){var q=G[t];if(q.element===k&&q.type===y&&q.fn===p){k=t;break a}}k=void 0}if(k)return y=G[k].wrapper,G.splice(k,1),y}var C=
w("custom-event"),x=w("./eventmap"),l=m.document,A=function(k,y,p,t){return k.addEventListener(y,p,t)},O=function(k,y,p,t){return k.removeEventListener(y,p,t)},G=[];m.addEventListener||(A=r,O=d);z.exports={add:A,remove:O,fabricate:function(k,y,p){if(-1===x.indexOf(y))p=new C(y,{detail:p});else{if(l.createEvent){var t=l.createEvent("Event");t.initEvent(y,!0,!0)}else l.createEventObject&&(t=l.createEventObject());p=t}k.dispatchEvent?k.dispatchEvent(p):k.fireEvent("on"+y,p)}}}).call(this,"undefined"!==
typeof global?global:"undefined"!==typeof self?self:"undefined"!==typeof window?window:{})},{"./eventmap":7,"custom-event":8}],7:[function(w,z,J){w="undefined"!==typeof global?global:"undefined"!==typeof self?self:"undefined"!==typeof window?window:{};J=[];var m="",r=/^on/;for(m in w)r.test(m)&&J.push(m.slice(2));z.exports=J},{}],8:[function(w,z,J){(function(m){var r=m.CustomEvent;z.exports=function(){try{var d=new r("cat",{detail:{foo:"bar"}});return"cat"===d.type&&"bar"===d.detail.foo}catch(h){}return!1}()?
r:"function"===typeof document.createEvent?function(d,h){var g=document.createEvent("CustomEvent");h?g.initCustomEvent(d,h.bubbles,h.cancelable,h.detail):g.initCustomEvent(d,!1,!1,void 0);return g}:function(d,h){var g=document.createEventObject();g.type=d;h?(g.bubbles=!!h.bubbles,g.cancelable=!!h.cancelable,g.detail=h.detail):(g.bubbles=!1,g.cancelable=!1,g.detail=void 0);return g}}).call(this,"undefined"!==typeof global?global:"undefined"!==typeof self?self:"undefined"!==typeof window?window:{})},
{}],9:[function(w,z,J){z.exports="function"===typeof setImmediate?function(m){setImmediate(m)}:function(m){setTimeout(m,0)}},{}]},{},[2])(2)});

((window, document) =>
{
	'use strict'

	XF.EditorManager = XF.Element.newHandler({
		options: {
			dragListClass: '.js-dragList',
			commandTrayClass: '.js-dragList-commandTray',
		},

		lists: null,
		trayElements: [],
		listElements: [],
		isScrollable: true,
		dragula: null,
		cache: null,

		xfEditor: null,

		init ()
		{
			this.lists = this.target.querySelectorAll(this.options.dragListClass)
			this.lists.forEach(list => this.prepareList(list))

			this.cache = this.target.querySelector('.js-dragListValue')

			this.initDragula()

			const xfEditor = XF.Element.getHandler(document.querySelector('textarea[name=button_layout_preview_html]'), 'editor')
			if (xfEditor)
			{
				this.xfEditor = xfEditor
				XF.on(xfEditor.target, 'editor:init', this.rebuildValueCache.bind(this))
			}
			else
			{
				this.rebuildValueCache()
			}
		},

		prepareList (list)
		{
			if (list.matches(this.options.commandTrayClass))
			{
				this.trayElements.push(list)
			}
			else
			{
				this.listElements[this.listElements.length] = list // not using .push() because I want them in order

				const listId = this.getListId(list)

				this.getListOptions(listId).forEach(option =>
				{
					XF.on(option, 'change', () => this.updateList(list, true))
				})
			}

			this.updateList(list)
		},

		initDragula ()
		{
			// the following is code to workaround an issue which makes the
			// page scroll while dragging elements.
			document.addEventListener('touchmove', e =>
			{
				if (!this.isScrollable)
				{
					e.preventDefault()
				}
			}, { passive: false })

			const lists = this.listElements

			for (let i in this.trayElements)
			{
				lists.unshift(this.trayElements[i])
			}

			this.dragula = dragula(lists, {
				direction: 'horizontal',
				removeOnSpill: true,
				copy: (el, source) =>
				{
					return this.isTrayElement(source)
				},
				accepts: (el, target) =>
				{
					return !this.isTrayElement(target)
				},
				moves: (el, source, handle, sibling) =>
				{
					return !el.classList.contains('toolbar-addDropdown') && !el.classList.contains('fr-separator')
				},
			})

			this.dragula.on('drag', this.drag.bind(this))
			this.dragula.on('dragend', this.dragend.bind(this))
			this.dragula.on('drop', this.drop.bind(this))
			this.dragula.on('cancel', this.cancel.bind(this))
			this.dragula.on('remove', this.remove.bind(this))
			this.dragula.on('over', this.over.bind(this))
			this.dragula.on('out', this.out.bind(this))
		},

		drag (el, source)
		{
			this.isScrollable = false

			if (el.classList.contains('toolbar-separator') && !source.classList.contains('js-dragList-commandTray'))
			{
				const elNext = el.nextElementSibling
				if (elNext && elNext.classList.contains('fr-separator'))
				{
					elNext.remove()
				}
			}
		},

		dragend (el)
		{
			this.isScrollable = true
			document.querySelector('.js-dropTarget')?.remove()
		},

		drop (el, target, source, sibling)
		{
			const cmd = el.dataset.cmd

			if (el.classList.contains('toolbar-separator'))
			{
				this.appendSeparator(el)
			}
			else
			{
				if (el.nextElementSibling?.matches('.fr-separator'))
				{
					el.nextElementSibling.after(el)
				}
			}

			// if dragged from our dropdown tray, remove the menu click attr
			if (el.getAttribute('data-xf-click') === 'menu')
			{
				el.removeAttribute('data-xf-click')
			}

			if (!this.isTrayElement(source))
			{
				this.updateList(source)
			}
			if (!this.isTrayElement(target))
			{
				this.updateList(target)
			}

			this.rebuildValueCache()
		},

		cancel (el, container, source)
		{
			if (el.classList.contains('toolbar-separator') && !source.classList.contains('js-dragList-commandTray'))
			{
				this.appendSeparator(el)
			}
		},

		remove (el, container, source)
		{
			if (!this.isTrayElement(source))
			{
				XF.flashMessage(XF.phrase('button_removed'), 1500)
				this.updateList(source, true)
			}
		},

		over (el, container, source)
		{
		},

		out (el, container, source)
		{
		},

		getListId (list)
		{
			return list.id.substr(12) // js-toolbar--$id
		},

		getListOptions (listId)
		{
			return document.querySelector(`#js-toolbar-menu--${ listId }`)
				?.querySelectorAll('input, select') || []
		},

		getListOptionValues (listId)
		{
			const optionValues = {
				buttons: [],
			}

			this.getListOptions(listId).forEach(formEl =>
			{
				optionValues[formEl.name] = formEl.value
			})

			return optionValues
		},

		updateList (list, rebuild)
		{
			const listId = this.getListId(list)
			const options = this.getListOptionValues(listId)

			let classesToRemove = Array.from(list.classList)
				.filter(className => className.match(/toolbar-option--[^\s$]+/g))
				.join(' ')

			classesToRemove.split(' ').forEach(cls =>
			{
				if (cls.length)
				{
					list.classList.remove(cls)
				}
			})

			list.classList.add('toolbar-option--buttonsVisible-' + options.buttonsVisible)
			list.classList.add('toolbar-option--align-' + options.align)

			if (rebuild)
			{
				this.rebuildValueCache()
			}
		},

		rebuildValueCache (e)
		{
			const options = {}

			if (!this.cache)
			{
				return
			}

			Array.from(this.lists)
				.filter(list => !list.classList.contains(this.options.commandTrayClass.slice(1)))
				.forEach(list =>
				{
					const listId = this.getListId(list)
					const listValue = this.getListOptionValues(listId)

					Array.from(list.children).forEach(cmd =>
					{
						if (!cmd.dataset.cmd)
						{
							return
						}
						listValue.buttons.push(cmd.dataset.cmd)
					})

					options[listId] = listValue
				})

			this.cache.value = JSON.stringify(options)

			// do not update editor preview if triggered by init
			const isInitTriggered = (e && e.type === 'editor:init')
			if (!isInitTriggered)
			{
				this.updateEditorPreview(options)
			}
		},

		updateEditorPreview (options)
		{
			const xfEditor = this.xfEditor
			const editorToolbar = document.querySelector('.js-editorToolbars')
			let cmd

			if (xfEditor && editorToolbar)
			{
				editorToolbar.innerHTML = JSON.stringify({ toolbarButtons: options })

				if (xfEditor.ed.$tb.hasClass('fr-toolbar-open'))
				{
					cmd = xfEditor.ed.$tb.find('.fr-btn.fr-open').first().data('cmd')
					xfEditor.reInit({
						afterInit ()
						{
							xfEditor.ed.commands[cmd]()
						},
					})
				}
				else
				{
					xfEditor.reInit()
				}
			}
		},

		appendSeparator (el)
		{
			const sep = XF.createElementFromString(`<div class="fr-separator fr${ el.dataset.cmd }"></div>`)
			el.after(sep)
		},

		isTrayElement (el)
		{
			return this.trayElements.includes(el)
		},
	})

	XF.Element.register('editor-manager', 'XF.EditorManager')
})(window, document)