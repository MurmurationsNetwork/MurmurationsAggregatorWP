import ReactDOM from 'react-dom/client'
import App from './app'

const tagSlug = getAttribute('data-tag-slug', '')
const view = getAttribute('data-view', 'map')
const height = getAttribute('data-height', 500, 'number')
const linkType = getAttribute('data-link-type', 'primary')

ReactDOM.createRoot(document.querySelector('#wp-map-plugin-page-root')).render(
  <App tagSlug={tagSlug} view={view} height={height} linkType={linkType} />
)

function getAttribute(name, defaultValue, type = 'string') {
  const value = document
    .querySelector('#wp-map-plugin-page-root')
    .getAttribute(name)

  if (type === 'number') {
    const number = parseInt(value)
    if (isNaN(number)) {
      return defaultValue
    }
    return number
  }

  if (value) {
    return value
  }
  return defaultValue
}
