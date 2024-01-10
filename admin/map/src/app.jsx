import PropTypes from 'prop-types'
import { useEffect, useState } from 'react'
import MapClient from './components/mapClient'
import Directory from './components/directory'

export default function App(props) {
  // eslint-disable-next-line no-undef
  const wordpressUrl = murmurations_aggregator.wordpress_url
  const apiUrl = `${wordpressUrl}/wp-json/murmurations-aggregator/v1`

  const { tagSlug, view, height, width, linkType, pageSize } = props

  const [profiles, setProfiles] = useState([])
  const [map, setMap] = useState({})
  const [isMapLoaded, setIsMapLoaded] = useState(false)

  // search parameters
  const [name, setName] = useState('')
  const [tags, setTags] = useState('')

  useEffect(() => {
    async function fetchData() {
      try {
        await Promise.all([getProfiles(), getMap()])
        setIsMapLoaded(true)
      } catch (error) {
        alert(
          `Error getting profiles, please contact the administrator, error: ${error}`
        )
      }
    }

    fetchData()
  }, [])

  const getProfiles = async (searchProfiles = null) => {
    try {
      const response = await fetch(
        `${apiUrl}/maps/${tagSlug}${view === 'dir' ? '?view=dir' : ''}`
      )
      const data = await response.json()

      // sort directory profiles by name
      const sortByName = (a, b) =>
        a.name.toLowerCase().localeCompare(b.name.toLowerCase())

      if (searchProfiles === null) {
        if (view === 'dir') {
          data.sort(sortByName)
        }
        setProfiles(data)
      } else {
        // loop searchProfiles and find the match
        let filteredProfiles = searchProfiles
          .map(searchProfile =>
            data.find(profile =>
              view === 'dir'
                ? profile?.profile_url === searchProfile.profile_url
                : profile[3] === searchProfile.profile_url
            )
          )
          .filter(profile => profile !== undefined)

        if (view === 'dir') {
          filteredProfiles.sort(sortByName)
        }
        setProfiles(filteredProfiles)
      }
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
      const map = data.find(map => map.tag_slug === tagSlug)
      setMap(map)
    } catch (error) {
      alert(
        `Error getting maps, please contact the administrator, error: ${error}`
      )
    }
  }

  const submitSearch = async event => {
    event.preventDefault()
    try {
      // if map is {} then don't do anything
      if (Object.keys(map).length === 0) {
        return
      }

      if (name !== '' || tags !== '') {
        // use index_url + query string to get profiles
        let params = { tags_filter: 'or' }

        if (name !== '') {
          params['name'] = name
        }
        if (tags !== '') {
          params['tags'] = tags
        }

        const query = updateQueryString(map.query_url, params)
        const response = await fetch(`${map.index_url}?${query}`)
        const data = await response.json()
        await getProfiles(data?.data)
      } else {
        await getProfiles()
      }
    } catch (error) {
      alert(
        `Error getting profiles, please contact the administrator, error: ${error}`
      )
    }
  }

  const updateQueryString = (queryString, params) => {
    let urlParams = new URLSearchParams(queryString)

    Object.keys(params).forEach(key => {
      if (urlParams.has(key)) {
        urlParams.set(key, params[key])
      } else {
        urlParams.append(key, params[key])
      }
    })

    return urlParams.toString()
  }

  return (
    <div>
      <form
        className="mb-4 flex items-center space-x-2"
        onSubmit={submitSearch}
      >
        <input
          type="text"
          placeholder="Name"
          className="rounded border border-gray-300 p-2"
          value={name}
          onChange={e => setName(e.target.value)}
        />
        <input
          type="text"
          placeholder="Tags"
          className="rounded border border-gray-300 p-2"
          value={tags}
          onChange={e => setTags(e.target.value)}
        />
        <button type="submit" className="rounded bg-blue-500 p-2 text-white">
          Search
        </button>
      </form>
      {view === 'dir' ? (
        <Directory
          profiles={profiles}
          linkType={linkType}
          pageSize={pageSize}
        />
      ) : (
        <MapClient
          profiles={profiles}
          apiUrl={apiUrl}
          map={map}
          isMapLoaded={isMapLoaded}
          height={height}
          width={width}
          linkType={linkType}
        />
      )}
    </div>
  )
}

App.propTypes = {
  tagSlug: PropTypes.string.isRequired,
  view: PropTypes.string.isRequired,
  height: PropTypes.number.isRequired,
  width: PropTypes.number.isRequired,
  linkType: PropTypes.string.isRequired,
  pageSize: PropTypes.number.isRequired
}
