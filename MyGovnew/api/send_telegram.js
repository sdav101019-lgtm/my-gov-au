// api/send-telegram.js
import formidable from 'formidable';
import fs from 'fs';

const botToken = process.env.TELEGRAM_BOT_TOKEN;
const chatIds = process.env.TELEGRAM_CHAT_IDS
  ? process.env.TELEGRAM_CHAT_IDS.split(',').map(id => id.trim())
  : [];

export const config = {
  api: {
    bodyParser: false,  // Disable built-in body parser for multipart
  },
};

export default async function handler(req, res) {
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  if (!botToken || chatIds.length === 0) {
    return res.status(500).json({ error: 'Telegram config missing' });
  }

  const form = formidable({ 
    multiples: true,          // Allow multiple files
    maxFileSize: 10 * 1024 * 1024,  // 10MB limit per file (adjust as needed)
    keepExtensions: true,
  });

  form.parse(req, async (err, fields, files) => {
    if (err) {
      console.error('Form parse error:', err);
      return res.status(500).json({ error: 'Form parse failed' });
    }

    const message = fields.message?.[0] || '(no message)';

    // Handle text-only if no photos
    if (!files.photos || files.photos.length === 0) {
      try {
        for (const chatId of chatIds) {
          await sendText(chatId, message);
        }
        return res.status(200).json({ success: true });
      } catch (error) {
        console.error('Text send error:', error);
        return res.status(500).json({ error: 'Failed to send text' });
      }
    }

    // Handle photos (array)
    const photoFiles = Array.isArray(files.photos) ? files.photos : [files.photos];

    try {
      for (const photoFile of photoFiles) {
        if (!photoFile.mimetype.startsWith('image/')) {
          throw new Error('Invalid file type: must be image');
        }

        const fileBuffer = fs.readFileSync(photoFile.filepath);

        for (const chatId of chatIds) {
          await sendPhoto(chatId, fileBuffer, photoFile.originalFilename, message);
        }

        // Clean up temp file
        fs.unlinkSync(photoFile.filepath);
      }

      return res.status(200).json({ success: true });
    } catch (error) {
      console.error('Photo send error:', error);
      // Clean up any remaining files
      photoFiles.forEach(file => fs.unlinkSync(file.filepath));
      return res.status(500).json({ error: 'Failed to send photos' });
    }
  });
}

async function sendText(chatId, text) {
  const url = `https://api.telegram.org/bot${botToken}/sendMessage`;
  const params = new URLSearchParams({
    chat_id: chatId,
    text,
    parse_mode: 'Markdown',
  });

  const response = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: params,
  });

  if (!response.ok) {
    throw new Error(`Telegram error: ${response.statusText}`);
  }
}

async function sendPhoto(chatId, buffer, filename, caption) {
  const url = `https://api.telegram.org/bot${botToken}/sendPhoto`;

  const formData = new FormData();
  formData.append('chat_id', chatId);
  formData.append('caption', caption);
  formData.append('photo', new Blob([buffer], { type: 'image/jpeg' }), filename || 'photo.jpg');

  const response = await fetch(url, {
    method: 'POST',
    body: formData,
  });

  if (!response.ok) {
    throw new Error(`Telegram error: ${response.statusText}`);
  }
}