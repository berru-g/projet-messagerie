<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

$user = getUserById($_SESSION['user_id']);

require_once '../includes/header.php';
?>
<style>
    :root {
        --bg: #1e1e1e;
        --text: #ccc;
        --border: #ab9ff2;
        --accent: #61dafb;
        --input-bg: #1e1e1e;
        --preview-bg: #fff;
    }

    body.light {
        --bg: #f9f9f9;
        --text: #222;
        --border: #ccc;
        --accent: #ab9ff2;
        --input-bg: #fff;
        --preview-bg: #f0f0f0;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Fira Code', monospace;
        background-color: var(--bg);
        color: var(--text);

    }

    .head {
        background-color: var(--bg);
        color: var(--text);
        text-align: center;
        padding: 1rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .head h1 {
        font-size: 1.3rem;
    }

    .controls {
        display: flex;
        gap: 10px;
        margin-right: 10px;
    }

    .controls button {
        background: none;
        border: 1px solid var(--text);
        color: var(--text);
        padding: 6px 12px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.8rem;
        transition: background 0.3s;
    }

    .controls button:hover {
        background: var(--border);
    }


    /* editeur */
    .main-layout {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .editor-panel {
        background-color: var(--input-bg);
        padding: 10px;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .editor-block {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    label {
        color: var(--accent);
        font-size: 0.9rem;
    }

    /* Textareas style */
    textarea.editor {
        background-color: var(--bg);
        color: var(--text);
        border-radius: 5px;
        padding: 10px;
        font-family: 'Fira Code', monospace;
        font-size: 0.9rem;
        resize: vertical;
        min-height: 150px;
        line-height: 1.5;
    }

    textarea.editor:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 2px #007acc33;
    }

    /* Output panel */
    .preview-panel {
        flex: 1;
        border-top: 1px solid #333;
        background-color: #fff;
    }

    .preview-panel iframe {
        width: 100%;
        min-height: 100vh;
        border: none;
    }

    /* Desktop layout */
    @media (min-width: 700px) {
        .main-layout {
            flex-direction: row;
        }

        .editor-panel {
            width: 35%;
            height: 100%;
            border-right: 1px solid #333;
            overflow-y: auto;
        }

        .preview-panel {
            width: 65%;
            height: 100%;
            border-top: none;
        }
    }
</style>


<div class="head">
    <h1>Live Code Editor</h1>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
    <div class="controls">
        <button id="toggleMode">🌙 Mode</button>
        <button id="reset">♻️ Reset</button>
        <a href="../"><button>⬅️ Back</button></a>
    </div>
</div>



<div class="main-layout">
    <div class="editor-panel">
        <div class="editor-block">
            <label for="html">HTML</label>
            <textarea id="html" class="editor"
                placeholder="Écris ton HTML ici..."><h1>Hello World</h1><p>What' up ?</p></textarea>
        </div>
        <div class="editor-block">
            <label for="css">CSS</label>
            <textarea id="css" class="editor" placeholder="Dev by berru-g">h1 {
  color: #ab9ff2;
  text-align: center;
  margin-top: 40px;
}</textarea>
        </div>
    </div>
    <div class="preview-panel">
        <iframe id="preview"></iframe>
    </div>
</div>





<script>
    document.getElementById("toggleMode").addEventListener("click", () => {
        document.body.classList.toggle("light");
    });

    document.getElementById("reset").addEventListener("click", () => {
        localStorage.clear();
        resetAll();
    });

    const html = document.getElementById("html");
    const css = document.getElementById("css");
    const iframe = document.querySelector("iframe");

    function updatePreview() {
        const content = `
         <html>
            <head>
               <style>${css.value}</style>
            </head>
            <body>${html.value}</body>
         </html>
      `;
        const preview = iframe.contentDocument || iframe.contentWindow.document;
        preview.open();
        preview.write(content);
        preview.close();
    }

    html.addEventListener("input", updatePreview);
    css.addEventListener("input", updatePreview);

    // Initial load
    updatePreview();
</script>


<?php require_once '../includes/footer.php'; ?>