import ReactDOM from 'react-dom/client'
import App from './app'

const tagSlug = document
  .querySelector('#wp-map-plugin-page-root')
  .getAttribute('data-tag-slug')

let view = document.querySelector('#wp-map-plugin-page-root').getAttribute('data-view')

if (view !== 'map' && view !== 'dict') {
  view = 'map'
}

let height = document.querySelector('#wp-map-plugin-page-root').getAttribute('data-height')

let heightNumber = parseInt(height)

if (isNaN(heightNumber)) {
  height = 500
} else {
  height = heightNumber
}

ReactDOM.createRoot(document.querySelector('#wp-map-plugin-page-root')).render(
  <App tagSlug={tagSlug} view={view} height={height} />
)
