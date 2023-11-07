const path = require('path')

module.exports = (env, argv) => {
  let publicPath
  if (argv.mode === 'production') {
    publicPath =
      '/wp-content/plugins/MurmurationsAggregatorWP/admin/assets/react/'
  } else {
    publicPath =
      '/wp-content/plugins/murmurations-aggregator/admin/assets/react/'
  }

  return {
    mode: 'development',
    entry: './src/index.js',
    output: {
      path: path.resolve(__dirname, '../assets/react'),
      filename: 'index.js',
      publicPath: publicPath
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