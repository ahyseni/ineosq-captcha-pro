import "react-app-polyfill/ie11";
import "react-app-polyfill/stable";
import React from "react";
import ReactDOM from "react-dom";
import App from "./components/App.js";

const cptch_containers = document.querySelectorAll( '#cptch_slide_captcha_container' );

for ( let item of cptch_containers ) {
	ReactDOM.render( <App/>, item );
}

jQuery( document ).on( 'nfFormReady', function() {
	const cptch_containers_nf = document.querySelector( '#cptch-nf #cptch_slide_captcha_container' );
    ReactDOM.render( <App/>, cptch_containers_nf );
} );

window.cptchSlideCaptchaRenderFunc = function() {
    const cptch_containers_bp = document.querySelector( '.activity-comments #cptch_slide_captcha_container' );
    ReactDOM.render( <App/>, cptch_containers_bp );
}

window.cptchSlideCaptchaRenderFuncWpf = function() {
    const cptch_containers_wpf = document.querySelector( '.wpf-topic-form-wrap #cptch_slide_captcha_container' );
    ReactDOM.render( <App/>, cptch_containers_wpf );
}
