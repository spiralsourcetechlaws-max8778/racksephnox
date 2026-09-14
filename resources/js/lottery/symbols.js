// Lottery symbol emoji map — shared across the framework
export const SYMBOL_EMOJI = {
    seven: '7️⃣', crown: '👑', diamond: '💎', gem: '💎',
    star: '⭐', bell: '🔔', coin: '🪙', clover: '🍀',
    cherry: '🍒', lemon: '🍋', orange: '🍊', grape: '🍇',
    watermelon: '🍉', apple: '🍎', divine: '🌟',
    default: '🎰',
};

export function emojiFor(name) {
    if (!name) return SYMBOL_EMOJI.default;
    const key = String(name).toLowerCase().replace(/[^a-z0-9]/g, '');
    return SYMBOL_EMOJI[key] || SYMBOL_EMOJI[name] || SYMBOL_EMOJI.default;
}

export default { SYMBOL_EMOJI, emojiFor };
