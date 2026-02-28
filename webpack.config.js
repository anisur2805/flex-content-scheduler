const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
  entry: {
    admin: './assets/src/admin/index.js',
    metabox: './assets/src/metabox/index.js'
  },
  output: {
    path: path.resolve(__dirname, 'assets/dist'),
    filename: '[name].js'
  },
  plugins: [new MiniCssExtractPlugin({ filename: '[name].css' })],
  module: {
    rules: [
      {
        test: /\.(js|jsx)$/,
        exclude: /node_modules/,
        use: { loader: 'babel-loader' }
      },
      {
        test: /\.scss$/,
        use: [MiniCssExtractPlugin.loader, 'css-loader', 'sass-loader']
      }
    ]
  },
  resolve: {
    extensions: ['.js', '.jsx']
  },
  externals: {
    react: 'React',
    'react-dom': 'ReactDOM'
  }
};
