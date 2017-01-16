# HTML Template for wiegerjelsma.com

These are the basic HTML5 templates for my portfolio website wiegerjelsma.com using parts of the Foundation CSS framework.

- Responsive
- Retina ready
- CSS3
- Balzing fast front end performance

---

# CSS & JS

The CSS file is generated from SCSS files. See `SCSS` directory for source files.
The Javascript source file is located in the `js` directory.

All CSS and Javascript files are compiled and minified to one file. Therefore we only have one http request per script language, which gives us a major performance boost. Javascript files are transpiled as well to decrease file size.

- js/min/app-min.js
- css/min/app-min.css

# imgInterchange (Javascript function)

The imgInterchange function loads the @0x image first, then preloads the @1x or @2x image (depending on screen resolution) and replaces the €0x image.
The €0x image is a blurry image, low in file size which appears very quick on the page. This technique gives the user a feel of a very fast website. Especially when large images on retina screens take a while to load.
