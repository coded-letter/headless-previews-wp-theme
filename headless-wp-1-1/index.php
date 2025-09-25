<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php wp_title('|', true, 'right'); ?></title>
  <?php wp_head(); ?>

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class'
    }
  </script>
  <!-- WordPress Base Styles -->
  <link rel="stylesheet"
    href="<?php echo get_template_directory_uri(); ?>/@wordpress/base-styles/build-style/admin-schemes.css">
  <style>
    /* TOC hidden by default (dot style) */
    .toc {
      position: fixed;
      top: 50%;
      right: 10px;
      transform: translateY(-50%);
      border-radius: 9999px;
      padding: 0.5rem;
      cursor: pointer;
      transition: all 0.3s ease;
      overflow: hidden;
      white-space: nowrap;
      width: 16px;
      height: 16px;
      z-index: 40;
    }

    .toc:hover {
      width: 200px;
      height: auto;
      border-radius: 0.5rem;
      padding: 1rem;
    }

    .toc:hover a {
      display: block;
    }

    .toc a {
      display: none;
      margin-bottom: 0.5rem;
      font-size: 0.875rem;
      color: #2563eb;
      transition: color 0.2s ease;
    }

    .toc a:hover {
      text-decoration: underline;
    }

    .toc a.active {
      font-weight: bold;
      color: #1d4ed8;
    }
  </style>
</head>

<body <?php body_class(); ?>>
  <div class="bg-gray-100 text-black dark:bg-gray-950 dark:text-gray-100">
    <button id="darkToggle" class="darkbtn fixed top-10 right-0 m-4 p-2 bg-gray-800 text-white rounded z-50">
      Dark mode
    </button>
    <div id="toc"
      class="toc bg-gray-100 border border-gray-300 hover:bg-white dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-900 shadow-md">
    </div>


    <main class="max-w-full mt-0 mx-auto p-4">
      <?php
      if (have_posts()):
        while (have_posts()):
          the_post(); ?>
          <h1 class="text-3xl container p-4 mx-auto font-bold mb-4"><?php the_title(); ?></h1>
          <div id="main-entry" class="content"><?php the_content(); ?></div>
          <?php
        endwhile;
      endif;
      ?>
    </main>

    <script>
      document.addEventListener('DOMContentLoaded', () => {
        // Apply saved dark mode preference
        if (localStorage.getItem('theme') === 'dark') {
          document.documentElement.classList.add('dark');
        }

        const content = document.querySelector('.content');
        const toc = document.getElementById('toc');

        if (content && toc) {
          const headings = content.querySelectorAll('h2'); // only H2
          const links = [];

          headings.forEach(h => {
            const id = h.textContent.trim().toLowerCase().replace(/\s+/g, '-');
            h.id = id;

            const link = document.createElement('a');
            link.href = '#' + id;
            link.textContent = h.textContent;
            toc.appendChild(link);
            links.push(link);
          });

          // IntersectionObserver for highlighting
          const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
              if (entry.isIntersecting) {
                const id = entry.target.id;
                links.forEach(link => {
                  link.classList.toggle('active', link.getAttribute('href') === '#' + id);
                });
              }
            });
          }, { rootMargin: '-20% 0px -60% 0px', threshold: 0 });

          headings.forEach(h => observer.observe(h));
        }

        // Dark mode toggle
        const toggle = document.getElementById('darkToggle');
        toggle.addEventListener('click', () => {
          document.documentElement.classList.toggle('dark');

          // Save preference
          if (document.documentElement.classList.contains('dark')) {
            localStorage.setItem('theme', 'dark');
          } else {
            localStorage.setItem('theme', 'light');
          }
        });
      });
    </script>

    <?php wp_footer(); ?>
  </div>
  <style>
    a::selection,
    b::selection,
    div::selection,
    h1::selection,
    h2::selection,
    h3::selection,
    h4::selection,
    h5::selection,
    h6::selection,
    li::selection,
    p::selection,
    span::selection,
    strong::selection {
      color: rgb(195, 137, 249) !important;
      background: rgb(26, 26, 26);
    }

    * {
      word-break: keep-all !important;
      word-wrap: keep-all !important;
    }

    html {
      scrollbar-width: thin;
      scroll-behavior: smooth;
    }

    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
      min-width: fit-content;
    }

    /* CSS Custom Properties Definitions */

    :root {
      --maxWidth-none: "none";
      --maxWidth-xs: 20rem;
      --maxWidth-sm: 24rem;
      --maxWidth-md: 28rem;
      --maxWidth-lg: 32rem;
      --maxWidth-xl: 36rem;
      --maxWidth-2xl: 42rem;
      --maxWidth-3xl: 48rem;
      --maxWidth-4xl: 56rem;
      --maxWidth-full: "100%";
      --maxWidth-wrapper: var(--maxWidth-2xl);
      --spacing-px: "1px";
      --spacing-0: 0;
      --spacing-1: 0.25rem;
      --spacing-2: 0.5rem;
      --spacing-3: 0.75rem;
      --spacing-4: 1rem;
      --spacing-5: 1.25rem;
      --spacing-6: 1.5rem;
      --spacing-8: 2rem;
      --spacing-10: 2.5rem;
      --spacing-12: 3rem;
      --spacing-16: 4rem;
      --spacing-20: 5rem;
      --spacing-24: 6rem;
      --spacing-32: 8rem;
      --fontFamily-sans: Montserrat, system-ui, -apple-system, BlinkMacSystemFont,
        "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif,
        "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
      --fontFamily-serif: "Merriweather", "Georgia", Cambria, "Times New Roman",
        Times, serif;
      --font-body: var(--fontFamily-serif);
      --font-heading: var(--fontFamily-sans);
      --fontWeight-normal: 400;
      --fontWeight-medium: 500;
      --fontWeight-semibold: 600;
      --fontWeight-bold: 700;
      --fontWeight-extrabold: 800;
      --fontWeight-black: 900;
      --fontSize-root: 16px;
      --lineHeight-none: 1;
      --lineHeight-tight: 1.1;
      --lineHeight-normal: 1.5;
      --lineHeight-relaxed: 1.625;
      /* 1.200 Minor Third Type Scale */
      --fontSize-0: 0.833rem;
      --fontSize-1: 1rem;
      --fontSize-2: 1.2rem;
      --fontSize-3: 1.44rem;
      --fontSize-4: 1.728rem;
      --fontSize-5: 2.074rem;
      --fontSize-6: 2.488rem;
      --fontSize-7: 2.986rem;
      --color-primary: #186193;
      --color-text: #2e353f;
      --color-text-light: #4f5969;
      --color-heading: #1a202c;
      --color-heading-black: black;
      --color-accent: #d1dce5;
    }

    /* HTML elements */

    html {
      scrollbar-width: thin;
      scrollbar-color: #6249f2 rgba(0, 0, 0, 0);
      scroll-behavior: smooth;
    }

    body::-webkit-scrollbar {
      width: 0.35em;
      z-index: 1000;
    }

    ::-webkit-scrollbar {
      width: 8px;
      height: 8px;
    }

    ::-webkit-scrollbar-thumb {
      background-color: #6249f2;
      border-radius: 5px;
    }

    body::-webkit-scrollbar-thumb {
      background-color: #6249f2;
      -webkit-transition: all 0.2s ease-in-out;
      transition: all 0.2s ease-in-out;
      cursor: -webkit-grabbing;
      cursor: grabbing;
    }

    body::-webkit-scrollbar-track {
      background-color: #fff;
      box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.1);
    }

    *,
    :after,
    :before {
      box-sizing: border-box;
    }

    html {
      line-height: var(--lineHeight-normal);
      font-size: var(--fontSize-root);
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    body {
      font-family: var(--font-body);
      font-size: var(--fontSize-1);
      color: var(--color-text);
    }

    footer {
      padding: var(--spacing-6) var(--spacing-0);
    }

    hr {
      background: var(--color-accent);
      height: 1px;
      border: 0;
    }

    /* Heading */

    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
      font-family: var(--font-heading);
      margin-top: var(--spacing-12);
      margin-bottom: var(--spacing-6);
      line-height: var(--lineHeight-tight);
      letter-spacing: -0.025em;
    }

    h2,
    h3,
    h4,
    h5,
    h6 {
      font-weight: var(--fontWeight-bold);
    }

    h1 {
      font-weight: var(--fontWeight-black);
      font-size: var(--fontSize-6);
    }

    h2 {
      font-size: var(--fontSize-5);
    }

    h3 {
      font-size: var(--fontSize-4);
    }

    h4 {
      font-size: var(--fontSize-3);
    }

    h5 {
      font-size: var(--fontSize-2);
    }

    h6 {
      font-size: var(--fontSize-1);
    }

    h1>a {
      color: inherit;
      text-decoration: none;
    }

    h2>a,
    h3>a,
    h4>a,
    h5>a,
    h6>a {
      text-decoration: none;
      color: inherit;
    }

    /* Prose */

    p {
      line-height: var(--lineHeight-relaxed);
      --baseline-multiplier: 0.179;
      --x-height-multiplier: 0.35;
      margin: var(--spacing-0) var(--spacing-0) var(--spacing-2) var(--spacing-0);
      padding: var(--spacing-0);
    }

    ul,
    ol {
      margin-left: var(--spacing-0);
      margin-right: var(--spacing-0);
      padding: var(--spacing-0);
      margin-bottom: var(--spacing-8);
      list-style-position: inside;
    }

    ul li,
    ol li {
      padding-left: var(--spacing-0);
      margin-bottom: calc(var(--spacing-8) / 2);
    }

    li>p {
      margin-bottom: calc(var(--spacing-8) / 2);
    }

    li *:last-child {
      margin-bottom: var(--spacing-0);
    }

    li>ul {
      margin-left: var(--spacing-8);
      margin-top: calc(var(--spacing-8) / 2);
    }

    blockquote {
      color: var(--color-text-light);
      margin-left: calc(-1 * var(--spacing-6));
      margin-right: var(--spacing-8);
      padding: var(--spacing-0) var(--spacing-0) var(--spacing-0) var(--spacing-6);
      border-left: var(--spacing-1) solid var(--color-primary);
      font-size: var(--fontSize-2);
      font-style: italic;
      margin-bottom: var(--spacing-8);
    }

    blockquote> :last-child {
      margin-bottom: var(--spacing-0);
    }

    blockquote>ul,
    blockquote>ol {
      list-style-position: inside;
    }

    table {
      width: 100%;
      margin-bottom: var(--spacing-8);
      border-collapse: collapse;
      border-spacing: 0.25rem;
    }

    table thead tr th {
      border-bottom: 1px solid var(--color-accent);
    }

    /* Link */

    a {
      color: var(--color-primary);
      word-break: break-word;
    }

    a:hover,
    a:focus {
      text-decoration: none;
    }

    /* Custom classes */

    .global-wrapper {
      margin: var(--spacing-0) auto;
      max-width: var(--maxWidth-wrapper);
      padding: var(--spacing-10) var(--spacing-5);
    }

    .global-wrapper[data-is-root-path="true"] .bio {
      margin-bottom: var(--spacing-20);
    }

    .global-header {
      margin-bottom: var(--spacing-12);
    }

    .main-heading {
      font-size: var(--fontSize-7);
      margin: 0;
    }

    .post-list-item {
      margin-bottom: var(--spacing-8);
      margin-top: var(--spacing-8);
    }

    .post-list-item p {
      margin-bottom: var(--spacing-0);
    }

    .post-list-item h2 {
      font-size: var(--fontSize-4);
      color: var(--color-primary);
      margin-bottom: var(--spacing-2);
      margin-top: var(--spacing-0);
    }

    .post-list-item header {
      margin-bottom: var(--spacing-4);
    }

    .header-link-home {
      font-weight: var(--fontWeight-bold);
      font-family: var(--font-heading);
      text-decoration: none;
      font-size: var(--fontSize-2);
    }

    .bio {
      display: flex;
      margin-bottom: var(--spacing-16);
    }

    @media screen and (max-width: 1024px) {
      .bio {
        flex-wrap: wrap;
      }
    }

    .bio p {
      margin-bottom: var(--spacing-0);
    }

    .bio-avatar {
      margin-right: var(--spacing-4);
      margin-bottom: var(--spacing-0);
      max-width: 75px;
      height: 75px;
      margin-bottom: 15px;
      object-fit: contain;
      border-radius: 100%;
    }

    .blog-post header h1 {
      margin: var(--spacing-0) var(--spacing-0) var(--spacing-4) var(--spacing-0);
    }

    .blog-post header p {
      font-size: var(--fontSize-2);
      font-family: var(--font-heading);
    }

    .blog-post-nav ul {
      margin: var(--spacing-0);
    }

    .gatsby-highlight {
      margin-bottom: var(--spacing-8);
    }

    /* Media queries */

    @media (max-width: 42rem) {
      blockquote {
        padding: var(--spacing-0) var(--spacing-0) var(--spacing-0) var(--spacing-4);
        margin-left: var(--spacing-0);
      }

      ul,
      ol {
        list-style-position: inside;
      }
    }

    @media (max-width: 500px) {
      #mob-menu {
        transform: scale(0.8);
        justify-content: flex-start;
        margin-left: -33px !important;
      }

      #promo {
        font-size: 12px;
      }
    }

    .animate-spin-slow>img {
      transition: transform 0.6s ease-in-out;
    }

    .animate-spin-slow:hover>img {
      transform: rotate(360deg);
    }

    #popup-overlay {
      cursor: pointer;
    }

    /*! normalize.css v8.0.1 | MIT License | github.com/necolas/normalize.css */

    /* Document
   ========================================================================== */

    /**
 * 1. Correct the line height in all browsers.
 * 2. Prevent adjustments of font size after orientation changes in iOS.
 */

    html {
      line-height: 1.15;
      /* 1 */
      -webkit-text-size-adjust: 100%;
      /* 2 */
    }

    /* Sections
   ========================================================================== */

    /**
 * Remove the margin in all browsers.
 */

    body {
      margin: 0;
    }

    /**
 * Render the `main` element consistently in IE.
 */

    main {
      display: block;
    }

    /**
 * Correct the font size and margin on `h1` elements within `section` and
 * `article` contexts in Chrome, Firefox, and Safari.
 */

    h1 {
      font-size: 2em;
      margin: 0.67em 0;
    }

    /* Grouping content
   ========================================================================== */

    /**
 * 1. Add the correct box sizing in Firefox.
 * 2. Show the overflow in Edge and IE.
 */

    hr {
      box-sizing: content-box;
      /* 1 */
      height: 0;
      /* 1 */
      overflow: visible;
      /* 2 */
    }

    /**
 * 1. Correct the inheritance and scaling of font size in all browsers.
 * 2. Correct the odd `em` font sizing in all browsers.
 */

    pre {
      font-family: monospace, monospace;
      /* 1 */
      font-size: 1em;
      /* 2 */
    }

    /* Text-level semantics
   ========================================================================== */

    /**
 * Remove the gray background on active links in IE 10.
 */

    a {
      background-color: transparent;
    }

    /**
 * 1. Remove the bottom border in Chrome 57-
 * 2. Add the correct text decoration in Chrome, Edge, IE, Opera, and Safari.
 */

    abbr[title] {
      border-bottom: none;
      /* 1 */
      text-decoration: underline;
      /* 2 */
      text-decoration: underline dotted;
      /* 2 */
    }

    /**
 * Add the correct font weight in Chrome, Edge, and Safari.
 */

    b,
    strong {
      font-weight: bolder;
    }

    /**
 * 1. Correct the inheritance and scaling of font size in all browsers.
 * 2. Correct the odd `em` font sizing in all browsers.
 */

    code,
    kbd,
    samp {
      font-family: monospace, monospace;
      /* 1 */
      font-size: 1em;
      /* 2 */
    }

    /**
 * Add the correct font size in all browsers.
 */

    small {
      font-size: 80%;
    }

    /**
 * Prevent `sub` and `sup` elements from affecting the line height in
 * all browsers.
 */

    sub,
    sup {
      font-size: 75%;
      line-height: 0;
      position: relative;
      vertical-align: baseline;
    }

    sub {
      bottom: -0.25em;
    }

    sup {
      top: -0.5em;
    }

    /* Embedded content
   ========================================================================== */

    /**
 * Remove the border on images inside links in IE 10.
 */

    img {
      border-style: none;
    }

    /* Forms
   ========================================================================== */

    /**
 * 1. Change the font styles in all browsers.
 * 2. Remove the margin in Firefox and Safari.
 */

    button,
    input,
    optgroup,
    select,
    textarea {
      font-family: inherit;
      /* 1 */
      font-size: 100%;
      /* 1 */
      line-height: 1.15;
      /* 1 */
      margin: 0;
      /* 2 */
    }

    /**
 * Show the overflow in IE.
 * 1. Show the overflow in Edge.
 */

    button,
    input {
      /* 1 */
      overflow: visible;
    }

    /**
 * Remove the inheritance of text transform in Edge, Firefox, and IE.
 * 1. Remove the inheritance of text transform in Firefox.
 */

    button,
    select {
      /* 1 */
      text-transform: none;
    }

    /**
 * Correct the inability to style clickable types in iOS and Safari.
 */

    button,
    [type="button"],
    [type="reset"],
    [type="submit"] {
      -webkit-appearance: button;
    }

    /**
 * Remove the inner border and padding in Firefox.
 */

    button::-moz-focus-inner,
    [type="button"]::-moz-focus-inner,
    [type="reset"]::-moz-focus-inner,
    [type="submit"]::-moz-focus-inner {
      border-style: none;
      padding: 0;
    }

    /**
 * Restore the focus styles unset by the previous rule.
 */

    button:-moz-focusring,
    [type="button"]:-moz-focusring,
    [type="reset"]:-moz-focusring,
    [type="submit"]:-moz-focusring {
      outline: 1px dotted ButtonText;
    }

    /**
 * Correct the padding in Firefox.
 */

    fieldset {
      padding: 0.35em 0.75em 0.625em;
    }

    /**
 * 1. Correct the text wrapping in Edge and IE.
 * 2. Correct the color inheritance from `fieldset` elements in IE.
 * 3. Remove the padding so developers are not caught out when they zero out
 *    `fieldset` elements in all browsers.
 */

    legend {
      box-sizing: border-box;
      /* 1 */
      color: inherit;
      /* 2 */
      display: table;
      /* 1 */
      max-width: 100%;
      /* 1 */
      padding: 0;
      /* 3 */
      white-space: normal;
      /* 1 */
    }

    /**
 * Add the correct vertical alignment in Chrome, Firefox, and Opera.
 */

    progress {
      vertical-align: baseline;
    }

    /**
 * Remove the default vertical scrollbar in IE 10+.
 */

    textarea {
      overflow: auto;
    }

    /**
 * 1. Add the correct box sizing in IE 10.
 * 2. Remove the padding in IE 10.
 */

    [type="checkbox"],
    [type="radio"] {
      box-sizing: border-box;
      /* 1 */
      padding: 0;
      /* 2 */
    }

    /**
 * Correct the cursor style of increment and decrement buttons in Chrome.
 */

    [type="number"]::-webkit-inner-spin-button,
    [type="number"]::-webkit-outer-spin-button {
      height: auto;
    }

    /**
 * 1. Correct the odd appearance in Chrome and Safari.
 * 2. Correct the outline style in Safari.
 */

    [type="search"] {
      -webkit-appearance: textfield;
      /* 1 */
      outline-offset: -2px;
      /* 2 */
    }

    /**
 * Remove the inner padding in Chrome and Safari on macOS.
 */

    [type="search"]::-webkit-search-decoration {
      -webkit-appearance: none;
    }

    /**
 * 1. Correct the inability to style clickable types in iOS and Safari.
 * 2. Change font properties to `inherit` in Safari.
 */

    ::-webkit-file-upload-button {
      -webkit-appearance: button;
      /* 1 */
      font: inherit;
      /* 2 */
    }

    /* Interactive
   ========================================================================== */

    /*
 * Add the correct display in Edge, IE 10+, and Firefox.
 */

    details {
      display: block;
    }

    /*
 * Add the correct display in all browsers.
 */

    summary {
      display: list-item;
    }

    /* Misc
   ========================================================================== */

    /**
 * Add the correct display in IE 10.
 */

    [hidden] {
      display: none;
    }


    #wp-admin-bar-headless-mods-menu>a,
    #wp-admin-bar-stripe-dashboard-menu>a {
      display: flex !important;
      align-items: center !important;
    }
  </style>


</body>

</html>