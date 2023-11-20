const path = require('path')

module.exports = () => {
  return {
    mode: 'development',
    entry: './src/index.js',
    output: {
      path: path.resolve(__dirname, '../assets/map'),
      filename: 'index.js',
      publicPath:
        '/wp-content/plugins/MurmurationsAggregatorWP/admin/assets/map/'
    },
    module: {
      rules: [
        {
          test: /\.(js|jsx)$/,
          exclude: /node_modules/,
          loader: 'babel-loader'
        },
        {
          test: /\.css$/,
          use: ['style-loader', 'css-loader']
        },
        {
          test: /\.png$/,
          type: 'asset/resource',
          generator: {
            filename: 'images/[name]-[hash][ext]'
          }
        }
      ]
    },
    devtool: 'source-map',
    resolve: {
      extensions: ['.js', '.jsx']
    },
    performance: {
      hints: false
    }
  }
}
