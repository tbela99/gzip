 /* font preloader script */
 "fonts" in document && "{WEB_FONTS}".forEach(font => new FontFace(font.fontFamily, font.src, font.properties).load().then(font => {

  document.fonts.add(font);
  console.info(`critical font loaded in ${(performance.now() / 1000).toFixed(3)}s`, {font})
 }));
