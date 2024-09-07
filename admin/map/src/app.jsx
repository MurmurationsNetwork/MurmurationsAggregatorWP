import PropTypes from 'prop-types'
import { useEffect, useRef, useState } from 'react'
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
  const [dropdownOptions, setDropdownOptions] = useState([])

  // search parameters
  const [name, setName] = useState('')
  const [tags, setTags] = useState('')
  const selectRefs = useRef([])

  useEffect(() => {
    async function fetchData() {
      try {
        await Promise.all([getProfiles(), getMap(), fetchDropdownOptions()])
        setIsMapLoaded(true)
      } catch (error) {
        alert(
          `Error getting profiles, please contact the administrator, error: ${error}`
        )
      }
    }

    fetchData()
  }, [])

  const getProfiles = async (queryURL = null) => {
    try {
      let fetchURL
      if (queryURL === null) {
        fetchURL = `${apiUrl}/maps/${tagSlug}${
          view === 'dir' ? '?view=dir' : ''
        }`
      } else {
        fetchURL =
          `${apiUrl}/maps/${tagSlug}${view === 'dir' ? '?view=dir' : '?'}` +
          queryURL
      }
      const response = await fetch(fetchURL)
      const data = await response.json()

      // sort directory profiles by name
      const sortByName = (a, b) =>
        a.name.toLowerCase().localeCompare(b.name.toLowerCase())

      if (view === 'dir') {
        data.sort(sortByName)
      }
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
      const map = data.find(map => map.tag_slug === tagSlug)
      setMap(map)
    } catch (error) {
      alert(
        `Error getting maps, please contact the administrator, error: ${error}`
      )
    }
  }

  const fetchDropdownOptions = async () => {
    try {
      const response = await fetch(
        `${apiUrl}/api/maps-dropdown?tag_slug=${tagSlug}`
      )
      if (!response.ok) {
        alert(
          `Error getting dropdown options, please contact the administrator, error: ${response.status}`
        )
        return
      }

      const data = await response.json()
      setDropdownOptions(data)
    } catch (error) {
      alert(
        `Error getting dropdown options, please contact the administrator, error: ${error}`
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

      const selectedValues = selectRefs.current.map(ref => ref?.value || '')

      if (name !== '' || tags !== '' || selectedValues.some(v => v !== '')) {
        let params = {}
        if (name !== '') {
          params['name'] = name
        }
        if (tags !== '') {
          params['tags'] = tags
        }

        // Add dropdown values to params
        dropdownOptions.forEach((dropdown, index) => {
          if (selectedValues[index] !== '') {
            params[dropdown.field_name] = selectedValues[index]
          }
        })

        const query = updateQueryString('', params)
        await getProfiles(query)
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
        {dropdownOptions.map((dropdown, index) => (
          <select
            key={dropdown.field_name}
            className="rounded border border-gray-300 p-2"
            ref={el => (selectRefs.current[index] = el)}
          >
            <option value="">{dropdown.title}</option>
            {dropdown.options.map(option => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
        ))}
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
