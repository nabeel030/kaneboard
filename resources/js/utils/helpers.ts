/**
 * Truncates a string to a specified length and adds an ellipsis.
 * * @param text - The string to be truncated.
 * @param limit - The maximum number of characters (default 250).
 * @returns The truncated string.
 */
export const truncateText = (text: string, limit: number = 250): string => {
  if (!text || text.length <= limit) {
    return text;
  }

  const truncated = text.substring(0, limit);
  const lastSpace = truncated.lastIndexOf(" ");

  // If there's a space within the last 20 chars, cut at the space for a cleaner look
  const cleanCut = (limit - lastSpace < 20) ? truncated.substring(0, lastSpace) : truncated;

  return `${cleanCut.trim()}...`;
};
