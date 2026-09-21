<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Paragraph Generator</title>
    <style>
        body {
            font-family: sans-serif;
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
        }

        textarea {
            width: 100%;
            height: 100px;
            margin-bottom: 10px;
            padding: 10px;
        }

        button {
            padding: 10px 20px;
            cursor: pointer;
            background: #0070f3;
            color: white;
            border: none;
            border-radius: 5px;
        }

        button:disabled {
            background: #ccc;
        }

        #output {
            margin-top: 20px;
            padding: 15px;
            background: #f0f0f0;
            border-radius: 5px;
            min-height: 50px;
            white-space: pre-wrap;
        }

        #status {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

    <h2>AI Paragraph Generator</h2>
    <div id="status">Loading AI Model (this takes a moment on first load)...</div>

    <textarea id="inputWords" placeholder="Enter a few words (e.g., puppy playing park)..." disabled></textarea>
    <button id="genBtn" onclick="generate()" disabled>Generate Paragraph</button>

    <h3>Result:</h3>
    <div id="output">AI is initializing...</div>

    <script type="module">
        import {
            pipeline
        } from 'https://jsdelivr.net';

        let generator;
        const status = document.getElementById('status');
        const output = document.getElementById('output');
        const inputWords = document.getElementById('inputWords');
        const genBtn = document.getElementById('genBtn');

        // Initialize the model globally
        async function init() {
            try {
                // Using Xenova/Qwen1.5-0.5B-Chat for better structured paragraph generation
                generator = await pipeline('text-generation', 'Xenova/Qwen1.5-0.5B-Chat');
                status.innerText = "AI Model Ready!";
                output.innerText = "Type your words above and click generate.";
                inputWords.disabled = false;
                genBtn.disabled = false;
            } catch (err) {
                status.innerText = "Error loading model: " + err.message;
            }
        }

        window.generate = async function() {
            const prompt = inputWords.value.trim();
            if (!prompt) return;

            status.innerText = "Generating paragraph...";
            genBtn.disabled = true;

            const formattedPrompt = `<|im_start|>user\nWrite a coherent, single paragraph using these exact keywords: ${prompt}<|im_end|>\n<|im_start||>assistant\n`;

            try {
                const results = await generator(formattedPrompt, {
                    max_new_tokens: 120,
                    temperature: 0.7,
                    do_sample: true
                });

                // Clean up the output text
                let text = results[0].generated_text;
                let cleanText = text.split('<|im_start|>assistant\n')[1] || text;
                cleanText = cleanText.replace('<|im_end|>', '').trim();

                output.innerText = cleanText;
            } catch (err) {
                output.innerText = "Generation failed: " + err.message;
            }

            status.innerText = "AI Model Ready!";
            genBtn.disabled = false;
        }

        init();
    </script>
</body>

</html>