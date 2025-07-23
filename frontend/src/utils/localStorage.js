// localStorage utilities with safe JSON parsing
export const localStorage_utils = {
  /**
   * Safely get and parse JSON data from localStorage
   * @param {string} key - localStorage key
   * @param {*} defaultValue - default value if parsing fails
   * @returns {*} parsed data or default value
   */
  getJSON(key, defaultValue = null) {
    try {
      const item = localStorage.getItem(key);
      return item ? JSON.parse(item) : defaultValue;
    } catch (error) {
      console.warn(`Error parsing localStorage item "${key}":`, error);
      localStorage.removeItem(key); // Clean up invalid data
      return defaultValue;
    }
  },

  /**
   * Safely set JSON data to localStorage
   * @param {string} key - localStorage key
   * @param {*} value - value to stringify and store
   */
  setJSON(key, value) {
    try {
      localStorage.setItem(key, JSON.stringify(value));
    } catch (error) {
      console.error(`Error storing item "${key}" to localStorage:`, error);
    }
  },

  /**
   * Remove item from localStorage
   * @param {string} key - localStorage key
   */
  remove(key) {
    localStorage.removeItem(key);
  },

  /**
   * Clear all localStorage data
   */
  clear() {
    localStorage.clear();
  }
};

export default localStorage_utils;
