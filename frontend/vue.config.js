module.exports = {
  lintOnSave: false,
  devServer: {
    port: 8084, // Updated to match current port
    host: 'localhost',
    open: true,
    proxy: {
      '/api': {
        target: 'http://localhost:3000',
        changeOrigin: true,
        secure: false,
        logLevel: 'debug'
      }
    }
  },
  configureWebpack: {
    devtool: 'source-map'
  }
}
