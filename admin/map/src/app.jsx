import PropTypes from 'prop-types'
import { useEffect, useState } from 'react'
import MapClient from './components/mapClient'

export default function App(props) {
  // eslint-disable-next-line no-undef
  const wordpressUrl = murmurations_aggregator.wordpress_url
  const apiUrl = `${wordpressUrl}/wp-json/murmurations-aggregator/v1`

  const { tagSlug, view } = props

  const [profiles, setProfiles] = useState([])
  const [map, setMap] = useState({})
  const [isMapLoaded, setIsMapLoaded] = useState(false)

  useEffect(() => {
    getProfiles().then(() => {
      console.log('profiles are loaded')
    })
    getMap().then(() => {
      setIsMapLoaded(true)
    })
  }, [])

  const getProfiles = async () => {
    try {
      let response
      if (view === 'dict') {
        response = await fetch(`${apiUrl}/maps/${tagSlug}?view=dict`)
      } else {
        response = await fetch(`${apiUrl}/maps/${tagSlug}`)
      }
      const data = await response.json()
      setProfiles(data)
    } catch (error) {
      alert(
        `Error getting profiles, please contact the administrator, error: ${error}`
      )
    }
  }

  const getMap = async () => {
    try {
      const response = await fetch(`${apiUrl}/api/maps`)
      const data = await response.json()
      const map = data.find((map) => map.tag_slug === tagSlug)
      setMap(map)
    } catch (error) {
      alert(
        `Error getting maps, please contact the administrator, error: ${error}`
      )
    }
  }

  return (
    <div>
      <h1 className="text-3xl">{map.name}</h1>
      { view === 'dict' ? (
        <div>
          <ul>
            {profiles.map((profile) => (
              <li key={profile.id}>
                <p>Profile Title: {profile.name}</p>
              </li>
            ))}
          </ul>
        </div>
      ) : (
        <MapClient
          profiles={profiles}
          apiUrl={apiUrl}
          map={map}
          isMapLoaded={isMapLoaded}
        />
      )}
    </div>
  )
}

App.propTypes = {
  tagSlug: PropTypes.string.isRequired,
  view: PropTypes.string.isRequired,
}
